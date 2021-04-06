<?php

declare(strict_types=1);
/**
 * This file is part of websocket-cluster-addon.
 *
 * @link     https://github.com/friendofhyperf/websocket-cluster-addon
 * @document https://github.com/friendofhyperf/websocket-cluster-addon/blob/main/README.md
 * @contact  huangdijia@gmail.com
 * @license  https://github.com/friendofhyperf/websocket-cluster-addon/blob/main/LICENSE
 */
namespace FriendsOfHyperf\WebsocketClusterAddon;

use FriendsOfHyperf\WebsocketClusterAddon\Connection\ConnectionInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Provider\ClientProviderInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Subscriber\SubscriberInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Hyperf\WebSocketServer\Sender;
use Psr\Container\ContainerInterface;
use Throwable;

class Addon
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var int
     */
    protected $workerId;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $serverId;

    /**
     * @var bool
     */
    protected $isRunning;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var SubscriberInterface
     */
    protected $subscriber;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var string
     */
    protected $channel;

    /**
     * Milliseconds.
     * @var int
     */
    protected $retryInterval = 1000;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->connection = $container->get(ConnectionInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->sender = $container->get(Sender::class);
        $this->subscriber = $container->get(SubscriberInterface::class);
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $this->channel = $config->get('websocket_cluster.subscriber.channel', 'wssa:channel');
        $this->prefix = $config->get('websocket_cluster.server.prefix', 'wssa:servers');
        $this->retryInterval = (int) $config->get('websocket_cluster.subscriber.retry_interval', 1000);
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.server.pool', 'default'));
    }

    public function setServerId(string $serverId): void
    {
        $this->serverId = $serverId;
    }

    public function getServerId(): string
    {
        return $this->serverId;
    }

    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function start(): void
    {
        $this->isRunning = true;

        $this->subscribe();
        $this->keepalive();
        $this->clearUpExpired();
    }

    public function stop(): void
    {
        $this->isRunning = false;
    }

    public function broadcast(string $payload): void
    {
        Coroutine::create(function () use ($payload) {
            $this->doBroadcast($payload, true);
        });

        $this->publish($this->getChannelKey(), $payload);
    }

    public function subscribe(): void
    {
        Coroutine::create(function () {
            CoordinatorManager::until(Constants::WORKER_START)->yield();

            retry(PHP_INT_MAX, function () {
                try {
                    $this->subscriber->subscribe($this->getChannelKey(), function ($payload) {
                        $this->doBroadcast($payload);
                    });
                } catch (Throwable $e) {
                    $this->logger->error((string) $e);
                    throw $e;
                }
            }, $this->retryInterval);
        });
    }

    public function keepalive(): void
    {
        Coroutine::create(function () {
            while (true) {
                if (! $this->isRunning) {
                    $this->logger->debug(sprintf('[WebsocketClusterAddon.%s] keepalive stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                $this->redis->zAdd($this->getServerListKey(), time(), $this->serverId);

                if (time() % 5 == 0) {
                    $this->logger->debug(sprintf('[WebsocketClusterAddon.%s] keepalive by %s', $this->serverId, __CLASS__));
                }

                sleep(1);
            }
        });
    }

    public function clearUpExpired(): void
    {
        Coroutine::create(function () {
            while (true) {
                if (! $this->isRunning) {
                    $this->logger->debug(sprintf('[WebsocketClusterAddon.%s] clearUpExpired stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                if ($expiredServers = $this->getExpiredServers()) {
                    /** @var ConnectionInterface $connection */
                    $connection = $this->container->get(ConnectionInterface::class);
                    $client = $this->container->get(ClientProviderInterface::class);

                    foreach ($expiredServers as $serverId) {
                        $connection->flush($serverId);
                        $client->flush($serverId);
                        $this->redis->zRem($this->getServerListKey(), $serverId);
                    }

                    $this->logger->info(sprintf('[WebsocketClusterAddon.%s] clear up by %s', $this->serverId, __CLASS__));
                }

                sleep(5);
            }
        });
    }

    public function getServers(): array
    {
        return $this->redis->zRangeByScore($this->getServerListKey(), '-inf', '+inf');
    }

    protected function getExpiredServers(): array
    {
        $start = '-inf';
        $end = (string) strtotime('-10 seconds');
        return $this->redis->zRangeByScore($this->getServerListKey(), $start, $end);
    }

    protected function publish(string $channel, string $payload): void
    {
        $this->redis->publish($channel, $payload);
    }

    protected function doBroadcast(string $payload, bool $isLocal = false): void
    {
        [$uid, $message, $serverId] = unserialize($payload);

        if (! $isLocal && $serverId == $this->serverId) {
            return;
        }

        $fds = $this->connection->all((int) $uid);

        foreach ($fds as $fd) {
            $this->sender->push($fd, $message);
        }
    }

    protected function getChannelKey(): string
    {
        return $this->channel;
    }

    protected function getServerListKey(): string
    {
        return join(':', [
            $this->prefix,
        ]);
    }
}
