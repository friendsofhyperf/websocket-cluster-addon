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
namespace FriendsOfHyperf\WebsocketClusterAddon\Controller;

use FriendsOfHyperf\WebsocketClusterAddon\Addon;
use FriendsOfHyperf\WebsocketClusterAddon\Provider\ClientProviderInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Provider\OnlineProviderInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

/**
 * @Controller(prefix="websocket")
 */
class InfoController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Addon
     */
    protected $addon;

    /**
     * @var OnlineProviderInterface
     */
    protected $online;

    /**
     * @var ClientProviderInterface
     */
    protected $client;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
        $this->redis = $container->get(RedisFactory::class)->get($this->config->get('websocket_cluster.server.pool', 'default'));
        $this->request = $container->get(RequestInterface::class);
        $this->addon = $container->get(Addon::class);
        $this->online = $container->get(OnlineProviderInterface::class);
        $this->client = $container->get(ClientProviderInterface::class);
    }

    /**
     * @GetMapping(path="info")
     */
    public function info()
    {
        if ($uid = $this->request->input('uid')) {
            $uid = (int) $uid;

            return [
                'online' => $this->online->get($uid),
                'clients' => $this->client->size($uid),
            ];
        }

        $redis = $this->redis;
        $servers = $this->addon->getServers();

        return collect($servers)
            ->transform(function ($pod) use ($redis) {
                $connections = 0;
                $pattern = sprintf('%s:%s:%s', $this->config->get('websocket_cluster.client.prefix'), $pod, '*');

                $users = collect($redis->keys($pattern))
                    ->each(function ($key) use ($redis, &$connections) {
                        $connections += $redis->sCard($key);
                    })
                    ->count();

                return [
                    'pod' => $pod,
                    'users' => $users,
                    'connections' => $connections,
                ];
            });
    }
}
