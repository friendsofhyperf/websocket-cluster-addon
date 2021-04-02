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
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerInterface;

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

    public function __construct(ContainerInterface $container)
    {
        $this->redis = $container->get(RedisFactory::class)->get($this->connection);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->container = $container;
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
        Coroutine::create(function () {
            while (true) {
                if (! $this->isRunning) {
                    $this->logger->info(sprintf('[WebSocketConnection.%s] stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                $this->keepalive();
                $this->logger->info(sprintf('[WebSocketConnection.%s] keepalive by %s', $this->serverId, __CLASS__));

                sleep(1);
            }
        });

        Coroutine::create(function () {
            while (true) {
                if (! $this->isRunning) {
                    $this->logger->info(sprintf('[WebSocketConnection.%s] stopped by %s', $this->serverId, __CLASS__));
                    break;
                }

                $this->clearUp();
                $this->logger->info(sprintf('[WebSocketConnection.%s] clear up by %s', $this->serverId, __CLASS__));

                sleep(3);
            }
        });
    }

    public function keepalive(): void
    {
        $this->redis->zAdd($this->getKey(), time(), $this->serverId);
    }

    public function all(): array
    {
        return $this->redis->zRangeByScore($this->getKey(), '-inf', '+inf');
    }

    public function clearUp(): void
    {
        $start = '-inf';
        $end = strtotime('-10 seconds');
        $expiredServers = $this->redis->zRangeByScore($this->getKey(), $start, $end);
        /** @var ConnectionInterface $connection */
        $connection = $this->container->get(ConnectionInterface::class);
        $client = $this->container->get(ClientProviderInterface::class);

        foreach ($expiredServers as $serverId) {
            $connection->flush($serverId);
            $client->flush($serverId);
            $this->redis->zRem($this->getKey(), $serverId);
        }
    }

    protected function getKey(): string
    {
        return join(':', [
            $this->prefix,
        ]);
    }
}
