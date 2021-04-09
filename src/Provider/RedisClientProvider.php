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
namespace FriendsOfHyperf\WebsocketClusterAddon\Provider;

use FriendsOfHyperf\WebsocketClusterAddon\Addon;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Parallel;
use Psr\Container\ContainerInterface;

class RedisClientProvider implements ClientProviderInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $serverId;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $this->prefix = $config->get('websocket_cluster.client.prefix', 'wssa:clients');
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.client.pool', 'default'));
    }

    public function add(int $fd, $uid): void
    {
        $key = $this->getKey($uid);

        $this->redis->multi();
        $this->redis->sAdd($key, $fd);
        $this->redis->expire($key, 172800);
        $this->redis->zAdd($this->getExpireKey(), time(), $uid);
        $this->redis->exec();
    }

    public function del(int $fd, $uid): void
    {
        $key = $this->getKey($uid);
        $this->redis->sRem($key, $fd);

        if ($this->redis->sCard($key) == 0) {
            $this->redis->zRem($this->getExpireKey(), $uid);
        }
    }

    public function renew($uid): void
    {
        $this->redis->zAdd($this->getExpireKey(), time(), $uid);
    }

    public function size($uid): int
    {
        /** @var Addon $addon */
        $addon = $this->container->get(Addon::class);
        $servers = $addon->getServers();
        $parallel = new Parallel();

        foreach ($servers as $serverId) {
            $parallel->add(function () use ($serverId, $uid) {
                return $this->redis->sCard($this->getKey($uid, $serverId));
            });
        }

        $result = $parallel->wait();

        return array_sum(array_values($result));
    }

    public function clearUpExpired(): void
    {
        $uids = $this->redis->zRangeByScore($this->getExpireKey(), '-inf', (string) strtotime('-60 seconds'));
        $this->redis->multi();

        foreach ($uids as $uid) {
            $this->redis->del($this->getKey($uid));
        }

        $this->redis->exec();
    }

    public function flush(string $serverId = null): void
    {
        $keys = $this->redis->keys($this->getKey('*', $serverId));
        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }

    protected function getServerId(): string
    {
        if (is_null($this->serverId)) {
            $this->serverId = $this->container->get(Addon::class)->getServerId();
        }

        return $this->serverId;
    }

    /**
     * @param int|string $uid
     */
    protected function getKey($uid, string $serverId = null): string
    {
        return join(':', [
            $this->prefix,
            $serverId ?? $this->getServerId(),
            $uid,
        ]);
    }

    protected function getExpireKey(string $serverId = null): string
    {
        return join(':', [$this->prefix, $serverId ?? $this->getServerId(), 'expire']);
    }
}
