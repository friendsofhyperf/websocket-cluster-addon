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
        $this->redis->sAdd($key, $fd);
        $this->redis->expire($key, 172800);
    }

    public function del(int $fd, $uid): void
    {
        $this->redis->sRem($this->getKey($uid), $fd);
    }

    public function renew(int $fd, $uid): void
    {
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

    /**
     * @param int|string $uid
     */
    protected function getKey($uid, string $serverId = null): string
    {
        return join(':', [
            $this->prefix,
            $serverId ?? $this->container->get(Addon::class)->getServerId(),
            $uid,
        ]);
    }
}
