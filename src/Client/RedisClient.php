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

use FriendsOfHyperf\WebsocketClusterAddon\Event\StatusChanged;
use FriendsOfHyperf\WebsocketClusterAddon\Status\StatusInterface;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RedisClient implements ClientInterface
{
    protected string $prefix;

    protected StatusInterface $status;

    public function __construct(protected ContainerInterface $container, protected Redis $redis, ConfigInterface $config, protected EventDispatcherInterface $eventDispatcher)
    {
        $this->prefix = $config->get('websocket_cluster.client.prefix', 'wsca:client');
        $this->status = make(StatusInterface::class, [
            'redis' => $redis,
            'key' => $this->getUserOnlineKey(),
        ]);
    }

    public function add(int $fd, $uid): void
    {
        // if ($this->redis->sAdd($this->getUserOnlineKey(), $uid)) {
        // }
        $this->status->set($uid, true);
        $this->eventDispatcher->dispatch(new StatusChanged($uid, 1));

        $this->redis->multi(\Redis::PIPELINE);
        $this->redis->sAdd($this->getUserClientKey($uid), $this->getSid($uid, $fd));
        $this->redis->zAdd($this->getUserActiveKey(), time(), $uid);
        $this->redis->exec();
    }

    public function renew(int $fd, $uid): void
    {
        $this->redis->zAdd($this->getUserActiveKey(), time(), $uid);
    }

    public function del(int $fd, $uid): void
    {
        $this->redis->sRem($this->getUserClientKey($uid), $this->getSid($uid, $fd));

        if ($this->size($uid) == 0) {
            // $this->redis->sRem($this->getUserOnlineKey(), $uid);
            $this->status->set($uid, false);
            $this->redis->zRem($this->getUserActiveKey(), $uid);

            $this->eventDispatcher->dispatch(new StatusChanged($uid, 0));
        }
    }

    public function clearUpExpired(): void
    {
        $uids = $this->redis->zRangeByScore($this->getUserActiveKey(), '-inf', (string) strtotime('-60 seconds'));

        if (! $uids) {
            return;
        }

        $this->redis->multi(\Redis::PIPELINE);

        foreach ($uids as $uid) {
            $this->redis->del($this->getUserClientKey($uid));
            // $this->redis->sRem($this->getUserOnlineKey(), $uid);
            $this->redis->zRem($this->getUserActiveKey(), $uid);
            $this->status->set($uid, false);
        }

        $this->redis->exec();
    }

    public function getOnlineStatus($uid): bool
    {
        // return $this->redis->sIsMember($this->getUserOnlineKey(), $uid);
        return $this->status->get($uid);
    }

    public function multiGetOnlineStatus(array $uids): array
    {
        // $uids = array_filter($uids);
        // $result = array_fill_keys($uids, false);
        // $tmpKey = $this->getOnlineTmpKey();

        // try {
        //     $this->redis->sAdd($tmpKey, ...$uids);
        //     $onlines = $this->redis->sInter($tmpKey, $this->getUserOnlineKey());
        //     $onlines = array_fill_keys($onlines, true);
        //     $result = array_replace($result, $onlines);
        // } finally {
        //     $this->redis->del($tmpKey);
        // }

        // return $result;

        return $this->status->multiGet($uids);
    }

    public function clients($uid): array
    {
        return $this->redis->sMembers($this->getUserClientKey($uid));
    }

    public function size($uid): int
    {
        if ($uid == 0) {
            // return $this->redis->sCard($this->getUserOnlineKey());
            return $this->status->count();
        }

        return $this->redis->sCard($this->getUserClientKey($uid));
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
}
