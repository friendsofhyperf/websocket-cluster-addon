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
namespace FriendsOfHyperf\WebsocketClusterAddon\Connection;

use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisConnection implements ConnectionInterface
{
    /**
     * @var string
     */
    protected $connection = 'default';

    /**
     * @var string
     */
    protected $prefix = 'wsc:connections';

    /**
     * @var \Hyperf\Redis\RedisProxy|Redis|\Redis
     */
    protected $redis;

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
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->redis = $container->get(RedisFactory::class)->get($this->connection);
    }

    public function add(int $fd, int $uid): void
    {
        $this->redis->sAdd($this->getKey($uid), $fd);
    }

    public function del(int $fd, int $uid): void
    {
        $this->redis->sRem($this->getKey($uid), $fd);
    }

    public function size(int $uid): int
    {
        return $this->redis->sCard($this->getKey($uid));
    }

    public function all(int $uid): array
    {
        return $this->redis->sMembers($this->getKey($uid));
    }

    public function flush(?string $serverId = null): void
    {
        $keys = $this->redis->keys($this->getKey('*', $serverId));
        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }

    protected function getKey($uid, string $serverId = null): string
    {
        return join(':', [
            $this->prefix,
            $serverId ?? $this->container->get(Server::class)->getServerId(),
            $uid,
        ]);
    }
}
