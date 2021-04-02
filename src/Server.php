<?php

declare(strict_types=1);
/**
 * This file is part of websocket-connection.
 *
 * @link     https://github.com/friendofhyperf/websocket-connection
 * @document https://github.com/friendofhyperf/websocket-connection/blob/main/README.md
 * @contact  huangdijia@gmail.com
 * @license  https://github.com/friendofhyperf/websocket-connection/blob/main/LICENSE
 */
namespace FriendsOfHyperf\WebsocketConnection;

use FriendsOfHyperf\WebsocketConnection\ClientProvider\ClientProviderInterface;
use FriendsOfHyperf\WebsocketConnection\Connection\ConnectionInterface;
use FriendsOfHyperf\WebsocketConnection\Subscriber\SubscriberInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Hyperf\WebSocketServer\Sender;
use Psr\Container\ContainerInterface;
use Throwable;

class Server
{
    protected $prefix = 'wsc:servers';

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
    protected $connection = 'default';

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
    protected $connectionProvider;

    /**
     * @var Sender
     */
    protected $sender;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->redis = $container->get(RedisFactory::class)->get($this->connection);
        $this->subscriber = $container->get(SubscriberInterface::class);
        $this->connectionProvider = $container->get(ConnectionInterface::class);
        $this->sender = $container->get(Sender::class);
    }

    public function setIsRunning(bool $isRunning): void
    {
        $this->isRunning = $isRunning;
    }

    public function setServerId(string $serverId): void
    {
        $this->serverId = $serverId;
    }

    public function getServerId(): string
    {
        return $this->serverId;
    }

    public function setWorkerId(int $workerId)
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

    public function publish(string $payload): void
    {
        $this->redis->publish($this->getChannelKey(), $payload);
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
            }, 1000);
        });
    }

    public function keepalive(): void
    {
        Coroutine::create(function () {
            while (true) {
                if (! $this->isRunning) {
                    $this->logger->info(sprintf('[WebSocketConnection.%s] keepalive stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                $this->redis->zAdd($this->getServerListKey(), time(), $this->serverId);
                $this->logger->info(sprintf('[WebSocketConnection.%s] keepalive by %s', $this->serverId, __CLASS__));

                sleep(1);
            }
        });
    }

    public function clearUpExpired(): void
    {
        Coroutine::create(function () {
            while (true) {
                if (! $this->isRunning) {
                    $this->logger->info(sprintf('[WebSocketConnection.%s] clearUpExpired stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                $start = '-inf';
                $end = (string) strtotime('-10 seconds');
                $expiredServers = $this->redis->zRangeByScore($this->getServerListKey(), $start, $end);
                /** @var ConnectionInterface $connection */
                $connection = $this->container->get(ConnectionInterface::class);
                $client = $this->container->get(ClientProviderInterface::class);

                foreach ($expiredServers as $serverId) {
                    $connection->flush($serverId);
                    $client->flush($serverId);
                    $this->redis->zRem($this->getServerListKey(), $serverId);
                }

                $this->logger->info(sprintf('[WebSocketConnection.%s] clear up by %s', $this->serverId, __CLASS__));

                sleep(3);
            }
        });
    }

    public function all(): array
    {
        return $this->redis->zRangeByScore($this->getServerListKey(), '-inf', '+inf');
    }

    protected function doBroadcast(string $payload): void
    {
        [$uid, $message] = unserialize($payload);

        $fds = $this->connectionProvider->all((int) $uid);

        foreach ($fds as $fd) {
            $this->sender->push($fd, $message);
        }
    }

    protected function getChannelKey(): string
    {
        return join(':', [
            $this->prefix,
            'channel',
        ]);
    }

    protected function getServerListKey(): string
    {
        return join(':', [
            $this->prefix,
        ]);
    }
}
