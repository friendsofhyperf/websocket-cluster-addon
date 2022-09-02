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
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RedisClient implements ClientInterface
{
    protected string $prefix;

    /**
     * @var \Redis
     */
    protected Redis $redis;

    protected ContainerInterface $container;

    protected StatusInterface $status;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $pool = $config->get('websocket_cluster.client.pool', 'default');
        $this->prefix = $config->get('websocket_cluster.client.prefix', 'wsca:client');
        $this->redis = $container->get(RedisFactory::class)->get($pool);
        $this->status = make(StatusInterface::class, [
            'redis' => $this->redis,
            'key' => $this->getUserOnlineKey(),
        ]);
    }

    public function add(int $fd, $uid): void
    {
        $this->status->set($uid, true);
        $this->container->get(EventDispatcherInterface::class)->dispatch(new StatusChanged($uid, 1));

        $this->redis->multi();
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
            $this->status->set($uid, false);
            $this->redis->zRem($this->getUserActiveKey(), $uid);

            $this->container->get(EventDispatcherInterface::class)->dispatch(new StatusChanged($uid, 0));
        }
    }

    public function clearUpExpired(): void
    {
        $uids = $this->redis->zRangeByScore($this->getUserActiveKey(), '-inf', (string) strtotime('-60 seconds'));

        if (! $uids) {
            return;
        }

        $this->redis->del(...array_map(fn ($uid) => $this->getUserClientKey($uid), $uids));
        $this->redis->zRem($this->getUserActiveKey(), ...$uids);
        $this->status->multiSet($uids, false);
    }

    public function getOnlineStatus($uid): bool
    {
        return $this->status->get($uid);
    }

    public function multiGetOnlineStatus(array $uids): array
    {
        return $this->status->multiGet($uids);
    }

    public function clients($uid): array
    {
        return $this->redis->sMembers($this->getUserClientKey($uid));
    }

    public function size($uid): int
    {
        if ($uid == 0) {
            return $this->status->count();
        }

        return $this->redis->sCard($this->getUserClientKey($uid));
    }

    public function getStatusInstance(): ?StatusInterface
    {
        return $this->status;
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
