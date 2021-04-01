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
namespace FriendsOfHyperf\WebsocketConnection\Client;

use FriendsOfHyperf\WebsocketConnection\Connection\ConnectionInterface;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface;

class RedisClient implements ClientInterface
{
    /**
     * @var string
     */
    protected $connection = 'default';

    /**
     * @var string
     */
    protected $prefix = 'ws-clients';

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $serverId;

    public function __construct(ContainerInterface $container, $redis)
    {
        $this->serverId = $container->get(ConnectionInterface::class)->getServerId();
        $this->redis = $container->get(RedisProxy::class)->get($this->connection);
    }

    public function add(int $fd, int $uid): void
    {
        $this->redis->sAdd($this->key($uid), $this->sid($fd));
    }

    public function del(int $fd, int $uid): void
    {
        $this->redis->sRem($this->key($uid), $this->sid($fd));
    }

    public function size(int $uid): int
    {
        return $this->redis->sCard($this->key($uid));
    }

    public function getFd(string $sid): ?int
    {
        return explode('#', $sid)[1] ?? null;
    }

    protected function key(int $uid): string
    {
        return sprintf('%s:%s', $this->prefix, $uid);
    }

    protected function sid(int $fd): string
    {
        return sprintf('%s#%s', $this->serverId, $fd);
    }
}
