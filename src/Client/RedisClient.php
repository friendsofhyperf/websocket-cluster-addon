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
namespace FriendsOfHyperf\WebsocketClusterAddon\Client;

use FriendsOfHyperf\WebsocketClusterAddon\Addon;
use FriendsOfHyperf\WebsocketClusterAddon\Event\StatusChanged;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RedisClient implements ClientInterface
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $pool = $config->get('websocket_cluster.client.pool', '');
        $this->redis = $container->get(RedisFactory::class)->get($pool);
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
    }

    public function add(int $fd, $uid): void
    {
        if ($this->redis->sAdd($this->getOnlineKey(), $uid)) {
            $this->eventDispatcher->dispatch(new StatusChanged($uid, 1));
        }

        $this->redis->multi();
        $this->redis->sAdd($this->getClientKey($uid), $this->getSid($uid, $fd));
        $this->redis->zAdd($this->getActiveKey(), time(), $uid);
        $this->redis->sAdd($this->getServerUserKey(), $uid);
        $this->redis->sAdd($this->getServerClientKey(), $this->getSid($uid, $fd));
        $this->redis->exec();
    }

    public function renew(int $fd, $uid): void
    {
        $this->redis->zAdd($this->getActiveKey(), time(), $uid);
    }

    public function del(int $fd, $uid): void
    {
        $this->redis->sRem($this->getClientKey($uid), $this->getSid($uid, $fd));

        if ($this->redis->sCard($this->getClientKey($uid)) == 0) {
            $this->redis->sRem($this->getOnlineKey(), $uid);
            $this->redis->zRem($this->getActiveKey(), $uid);

            $this->eventDispatcher->dispatch(new StatusChanged($uid, 0));
        }

        // todo
        $this->redis->sRem($this->getServerClientKey(), $this->getSid($uid, $fd));
        $this->redis->sRem($this->getServerUserKey(), $uid);
    }

    public function cleanup(): void
    {
        $uids = $this->redis->zRangeByScore($this->getActiveKey(), '-inf', (string) strtotime('-60 seconds'));

        $this->redis->multi();

        foreach ($uids as $uid) {
            $this->redis->del($this->getClientKey($uid));
            $this->redis->sRem($this->getOnlineKey(), $uid);
            $this->redis->zRem($this->getActiveKey(), $uid);
        }

        $this->redis->exec();
    }

    public function getOnlineStatus($uid): bool
    {
        return $this->redis->sIsMember($this->getOnlineKey(), $uid);
    }

    public function multiGetOnlineStatus(array $uids): array
    {
        $uids = array_filter($uids);
        $result = array_fill_keys($uids, false);
        $tmpKey = $this->getOnlineTmpKey();

        try {
            // tmp
            $this->redis->sAdd($tmpKey, ...$uids);

            // intersection
            $onlines = $this->redis->sInter($tmpKey, $this->getOnlineKey());
            $onlines = array_fill_keys($onlines, true);

            // array merge
            $result = array_replace($result, $onlines);
        } finally {
            $this->redis->del($tmpKey);
        }

        return $result;
    }

    public function getServerClientKey(?string $serverId = null)
    {
        return join(':', [$this->getOnlineKey(), $serverId ?? $this->getServerId(), 'clients']);
    }

    public function getServerUserKey(?string $serverId = null)
    {
        return join(':', [$this->getOnlineKey(), $serverId ?? $this->getServerId(), 'users']);
    }

    protected function getSid($uid, int $fd)
    {
        return join('#', [$uid, $fd]);
    }

    protected function getServerId(): string
    {
        return $this->container->get(Addon::class)->getServerId();
    }

    protected function getOnlineKey(): string
    {
        return join(':', [$this->prefix, 'online_users']);
    }

    protected function getActiveKey(): string
    {
        return join(':', [$this->getOnlineKey(), 'active']);
    }

    protected function getClientKey($uid): string
    {
        return join(':', [$this->getOnlineKey(), $uid]);
    }

    protected function getOnlineTmpKey(): string
    {
        return join(':', [$this->getOnlineKey(), uniqid()]);
    }
}
