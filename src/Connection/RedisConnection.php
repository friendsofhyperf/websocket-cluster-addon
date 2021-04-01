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
namespace FriendsOfHyperf\WebsocketConnection\Connection;

use FriendsOfHyperf\WebsocketConnection\ConnectionInterface;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisProxy;
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
    protected $prefix = 'websocket-connection';

    /**
     * @var \Hyperf\Redis\RedisProxy|Redis|\Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $serverId;

    public function __construct(ContainerInterface $container)
    {
        $this->redis = $container->get(RedisProxy::class)->get($this->connection);
        $this->serverId = uniqid();
    }

    public function add(int $fd, int $uid): void
    {
        $this->redis->sAdd($this->key($uid), $fd);
    }

    public function del(int $fd, int $uid): void
    {
        $this->redis->sRem($this->key($uid), $fd);
    }

    public function size(int $uid): int
    {
        return $this->redis->sCard($this->key($uid));
    }

    public function all(int $uid): array
    {
        return $this->redis->sMembers($this->key($uid));
    }

    public function flush(): void
    {
        $keys = $this->redis->keys($this->key('*'));
        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }

    protected function key($uid): string
    {
        return sprintf('%s:%s:%s', $this->prefix, $this->serverId, $uid);
    }
}
