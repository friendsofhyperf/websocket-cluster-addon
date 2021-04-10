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
        if ($this->redis->sAdd($this->getUserOnlineKey(), $uid)) {
            $this->eventDispatcher->dispatch(new StatusChanged($uid, 1));
        }

        $this->redis->multi();
        $this->redis->sAdd($this->getUserClientKey($uid), $this->getSid($uid, $fd));
        $this->redis->zAdd($this->getUserActiveKey(), time(), $uid);
        $this->redis->sAdd($this->getServerClientKey(), $this->getSid($uid, $fd));
        $this->redis->exec();
    }

    public function renew(int $fd, $uid): void
    {
        $this->redis->zAdd($this->getUserActiveKey(), time(), $uid);
    }

    public function del(int $fd, $uid): void
    {
        $this->redis->sRem($this->getUserClientKey($uid), $this->getSid($uid, $fd));
        $this->redis->sRem($this->getServerClientKey(), $this->getSid($uid, $fd));

        if ($this->redis->sCard($this->getUserClientKey($uid)) == 0) {
            $this->redis->sRem($this->getUserOnlineKey(), $uid);
            $this->redis->zRem($this->getUserActiveKey(), $uid);

            $this->eventDispatcher->dispatch(new StatusChanged($uid, 0));
        }
    }

    public function cleanup(): void
    {
        $uids = $this->redis->zRangeByScore($this->getUserActiveKey(), '-inf', (string) strtotime('-60 seconds'));

        $this->redis->multi();

        foreach ($uids as $uid) {
            $sids = $this->redis->sMembers($this->getUserClientKey($uid));
            // todo remove sids from all nodes
            $this->redis->del($this->getUserClientKey($uid));
            $this->redis->sRem($this->getUserOnlineKey(), $uid);
            $this->redis->zRem($this->getUserActiveKey(), $uid);
        }

        $this->redis->exec();
    }

    public function getOnlineStatus($uid): bool
    {
        return $this->redis->sIsMember($this->getUserOnlineKey(), $uid);
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
            $onlines = $this->redis->sInter($tmpKey, $this->getUserOnlineKey());
            $onlines = array_fill_keys($onlines, true);

            // array merge
            $result = array_replace($result, $onlines);
        } finally {
            $this->redis->del($tmpKey);
        }

        return $result;
    }

    public function clientsOfUser($uid): array
    {
        return $this->redis->sMembers($this->getUserClientKey($uid));
    }

    public function usersOfNode(?string $serverId = null): array
    {
        $users = [];

        foreach ($this->clientsOfNode($serverId) as $sid) {
            $users[$this->getUid($sid)] = true;
        }

        return array_keys($users);
    }

    public function clientsOfNode(?string $serverId = null): array
    {
        return $this->redis->sMembers($this->getServerClientKey($serverId));
    }

    public function cleanupClientsOfNode(?string $serverId = null): void
    {
        $this->redis->del($this->getServerClientKey($serverId));
    }

    /**
     * @return null|string
     */
    protected function getUid(string $sid)
    {
        return explode('#', $sid)[0] ?? null;
    }

    protected function getSid($uid, int $fd)
    {
        return join('#', [$uid, $fd]);
    }

    protected function getServerId(): string
    {
        return $this->container->get(Addon::class)->getServerId();
    }

    protected function getUserOnlineKey(): string
    {
        return join(':', [$this->prefix, 'online_users']);
    }

    protected function getUserActiveKey(): string
    {
        return join(':', [$this->getUserOnlineKey(), 'active']);
    }

    protected function getUserClientKey($uid): string
    {
        return join(':', [$this->getUserOnlineKey(), $uid]);
    }

    protected function getOnlineTmpKey(): string
    {
        return join(':', [$this->getUserOnlineKey(), uniqid()]);
    }

    protected function getServerClientKey(?string $serverId = null)
    {
        return join(':', [$this->getUserOnlineKey(), $serverId ?? $this->getServerId()]);
    }
}
