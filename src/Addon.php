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
use FriendsOfHyperf\WebsocketClusterAddon\Provider\OnlineProviderInterface;
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
     * @var ConnectionInterface
     */
    protected $connectionProvider;

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

    /**
     * @var OnlineProviderInterface
     */
    protected $onlineProvider;

    /**
     * @var ClientProviderInterface
     */
    protected $clientProvider;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->connectionProvider = $container->get(ConnectionInterface::class);
        $this->clientProvider = $container->get(ClientProviderInterface::class);
        $this->onlineProvider = $container->get(OnlineProviderInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->sender = $container->get(Sender::class);
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

    public function getServerId(): ?string
    {
        return $this->serverId;
    }

    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }

    public function getWorkerId(): ?int
    {
        return $this->workerId;
    }

    public function start(): void
    {
        $this->isRunning = true;

        $this->subscribe();
        $this->keepalive();
        $this->monitoring();
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
                    // fix Uncaught Swoole\Error: API must be called in the coroutine when $subscriber instanceof \Mix\Redis\Subscribe\Subscriber
                    $subscriber = make(SubscriberInterface::class);

                    $subscriber->subscribe($this->getChannelKey(), function ($channel, $payload) {
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
            CoordinatorManager::until(Constants::WORKER_START)->yield();

            while (true) {
                if (! $this->isRunning) {
                    $this->logger->info(sprintf('[WebsocketClusterAddon] @%s keepalive stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                $this->redis->zAdd($this->getServerListKey(), time(), $this->serverId);

                if (time() % 5 == 0) {
                    $this->logger->debug(sprintf('[WebsocketClusterAddon] @%s keepalive by %s', $this->serverId, __CLASS__));
                }

                sleep(1);
            }
        });
    }

    public function clearUpExpired(): void
    {
        Coroutine::create(function () {
            CoordinatorManager::until(Constants::WORKER_START)->yield();

            while (true) {
                if (! $this->isRunning) {
                    $this->logger->info(sprintf('[WebsocketClusterAddon] @%s clearUpExpired stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                // Clear up expired servers
                $this->clearUpExpiredServers();

                // Clear up expired users
                $this->onlineProvider->clearUpExpired();

                // Clear up expired clients
                $this->clientProvider->clearUpExpired();

                sleep(5);
            }
        });
    }

    public function monitoring(): void
    {
        Coroutine::create(function () {
            CoordinatorManager::until(Constants::WORKER_START)->yield();

            while (true) {
                if (! $this->isRunning) {
                    $this->logger->info(sprintf('[WebsocketClusterAddon] @%s monitoring stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                $data = [
                    'users' => $this->connectionProvider->users(),
                    'clients' => $this->connectionProvider->size(0),
                ];

                $this->redis->hSet($this->getMonitorKey(), $this->serverId, json_encode($data));

                if (time() % 5 == 0) {
                    $this->logger->debug(sprintf('[WebsocketClusterAddon] @%s monitoring by %s', $this->serverId, __CLASS__));
                }

                sleep(1);
            }
        });
    }

    public function getServers(): array
    {
        return $this->redis->zRangeByScore($this->getServerListKey(), '-inf', '+inf');
    }

    public function getMonitors(): array
    {
        return collect($this->redis->hGetAll($this->getMonitorKey()))
            ->transform(function ($item) {
                return json_decode($item, true);
            })
            ->toArray();
    }

    protected function clearUpExpiredServers(): void
    {
        $start = '-inf';
        $end = (string) strtotime('-10 seconds');
        $expiredServers = $this->redis->zRangeByScore($this->getServerListKey(), $start, $end);

        if (! $expiredServers) {
            return;
        }

        foreach ($expiredServers as $serverId) {
            $this->connectionProvider->flush($serverId);
            $this->clientProvider->flush($serverId);
        }

        $this->redis->zRem($this->getServerListKey(), ...$expiredServers);
        $this->redis->hDel($this->getMonitorKey(), ...$expiredServers);

        $this->logger->info(sprintf('[WebsocketClusterAddon] @%s clear up expired servers by %s', $this->serverId, __CLASS__));
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

        $clients = $this->connectionProvider->clients($uid);

        foreach ($clients as $fd) {
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

    protected function getMonitorKey(): string
    {
        return join(':', [$this->prefix, 'monitoring']);
    }
}
