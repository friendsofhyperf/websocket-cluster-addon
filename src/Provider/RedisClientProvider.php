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
use Hyperf\Contract\StdoutLoggerInterface;
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

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $this->prefix = $config->get('websocket_cluster.client.prefix', 'wssa:clients');
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.client.pool', 'default'));
        $this->logger = $config->get(StdoutLoggerInterface::class);
    }

    public function add(int $fd, $uid): void
    {
        $this->redis->zAdd($this->getKey(), time(), $this->getSid($uid, $fd));
    }

    public function del(int $fd, $uid): void
    {
        $this->redis->zRem($this->getKey(), $this->getSid($uid, $fd));
    }

    public function renew(int $fd, $uid): void
    {
        $this->redis->zAdd($this->getKey(), time(), $this->getSid($uid, $fd));
    }

    public function size($uid): int
    {
        /** @var Addon $addon */
        $addon = $this->container->get(Addon::class);
        $servers = $addon->getServers();
        $parallel = new Parallel();

        foreach ($servers as $serverId) {
            $parallel->add(function () use ($serverId, $uid) {
                return collect($this->redis->zRange($this->getKey($serverId), 0, -1))
                    ->reject(function ($sid) use ($uid) {
                        return $this->getUid($sid) != $uid;
                    })
                    ->count();
            });
        }

        $result = $parallel->wait();

        return array_sum(array_values($result));
    }

    public function clearUpExpired(): void
    {
        $deleted = $this->redis->zRemRangeByScore($this->getKey(), '-inf', (string) strtotime('-120 seconds'));

        if ($deleted) {
            $this->logger->info(sprintf('[WebsocketClusterAddon] @%s clear up expired clients by %s', $this->container->get(Addon::class)->getServerId(), __CLASS__));
        }
    }

    public function flush(string $serverId = null): void
    {
        $this->redis->del($this->getKey($serverId));
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
    protected function getKey(string $serverId = null): string
    {
        return join(':', [
            $this->prefix,
            $serverId ?? $this->getServerId(),
        ]);
    }

    protected function getSid($uid, int $fd): string
    {
        return join('#', [$uid, $fd]);
    }

    /**
     * @return int|string
     */
    protected function getUid(string $sid)
    {
        return explode('#', $sid)[0] ?? '';
    }

    protected function getFd(string $sid): int
    {
        return explode('#', $sid)[1] ?? 0;
    }
}
