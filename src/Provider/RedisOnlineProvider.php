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
use FriendsOfHyperf\WebsocketClusterAddon\Event\StatusChanged;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RedisOnlineProvider implements OnlineProviderInterface
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
     * @var ClientProviderInterface
     */
    protected $clientProvider;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->clientProvider = $container->get(ClientProviderInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $this->prefix = $config->get('websocket_cluster.online.prefix', 'wssa:online');
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.online.pool', 'default'));
    }

    public function add($uid): void
    {
        if ($this->redis->sAdd($this->getKey(), $uid)) {
            $this->container->get(EventDispatcherInterface::class)->dispatch(new StatusChanged($uid, 1));
        }

        $this->redis->zAdd($this->getExpireKey(), time(), $uid);
    }

    public function del($uid): void
    {
        if ($this->clientProvider->size($uid) > 0) {
            return;
        }

        if ($this->redis->sRem($this->getKey(), $uid)) {
            $this->container->get(EventDispatcherInterface::class)->dispatch(new StatusChanged($uid, 0));
        }

        $this->redis->zRem($this->getExpireKey(), $uid);
    }

    public function renew($uid): void
    {
        $this->redis->zAdd($this->getExpireKey(), time(), $uid);
    }

    public function get($uid): bool
    {
        return $this->redis->sIsMember($this->getKey(), $uid);
    }

    public function multiGet(array $uids): array
    {
        $uids = array_filter($uids);
        $result = array_fill_keys($uids, false);
        $tmpKey = $this->getTmpKey();

        try {
            // tmp
            $this->redis->sAdd($tmpKey, ...$uids);

            // intersection
            $onlines = $this->redis->sInter($tmpKey, $this->getKey());
            $onlines = array_fill_keys($onlines, true);

            // array merge
            $result = array_replace($result, $onlines);
        } finally {
            $this->redis->del($tmpKey);
        }

        return $result;
    }

    public function clearUpExpired(): void
    {
        $uids = $this->redis->zRangeByScore($this->getExpireKey(), '-inf', (string) strtotime('-120 seconds'));

        if (! $uids) {
            return;
        }

        $deleted = 0;
        $deleted += $this->redis->sRem($this->getKey(), ...$uids);
        $deleted += $this->redis->zRem($this->getExpireKey(), ...$uids);

        if ($deleted) {
            $this->logger->info(sprintf('[WebsocketClusterAddon] @%s clear up expired online by %s', $this->container->get(Addon::class)->getServerId(), __CLASS__));
        }
    }

    protected function getKey(): string
    {
        return $this->prefix;
    }

    protected function getTmpKey(): string
    {
        return join(':', [$this->prefix, uniqid()]);
    }

    protected function getExpireKey(): string
    {
        return join(':', [$this->prefix, 'expire']);
    }
}
