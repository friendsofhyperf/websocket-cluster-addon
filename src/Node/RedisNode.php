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
namespace FriendsOfHyperf\WebsocketClusterAddon\Node;

use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisNode implements NodeInterface
{
    protected string $prefix;

    protected Redis $redis;

    public function __construct(protected ContainerInterface $container, protected StdoutLoggerInterface $logger, ConfigInterface $config)
    {
        $this->prefix = $config->get('websocket_cluster.node.prefix', 'wsca:node');
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.node.pool', 'default'));
    }

    public function add(int $fd, $uid): void
    {
        $this->redis->sAdd($this->getKey($uid), $fd);
        $this->redis->sAdd($this->getKey(0), $fd);
    }

    public function del(int $fd, $uid): void
    {
        $this->redis->sRem($this->getKey($uid), $fd);
        $this->redis->sRem($this->getKey(0), $fd);
    }

    public function users(): int
    {
        $num = count($this->redis->keys($this->getKey('*')));

        return $num > 0 ? ($num - 1) : $num;
    }

    public function clients($uid): array
    {
        return $this->redis->sMembers($this->getKey($uid));
    }

    public function size($uid): int
    {
        return $this->redis->sCard($this->getKey($uid));
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
