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

use FriendsOfHyperf\WebsocketClusterAddon\Event\StatusChanged;
use Hyperf\Contract\ConfigInterface;
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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->clientProvider = $container->get(ClientProviderInterface::class);
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
    }

    public function del($uid): void
    {
        if ($this->clientProvider->size($uid) > 0) {
            return;
        }

        if ($this->redis->sRem($this->getKey(), $uid)) {
            $this->container->get(EventDispatcherInterface::class)->dispatch(new StatusChanged($uid, 0));
        }
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

    protected function getTmpKey(): string
    {
        return join(':', [$this->prefix, uniqid()]);
    }

    protected function getKey(): string
    {
        return $this->prefix;
    }
}
