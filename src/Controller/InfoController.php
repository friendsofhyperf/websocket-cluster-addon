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
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
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
    protected $onlineProvider;

    /**
     * @var ClientProviderInterface
     */
    protected $clientProvider;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request = $container->get(RequestInterface::class);
        $this->addon = $container->get(Addon::class);
        $this->onlineProvider = $container->get(OnlineProviderInterface::class);
        $this->clientProvider = $container->get(ClientProviderInterface::class);
    }

    /**
     * @GetMapping(path="info")
     */
    public function info()
    {
        if ($uid = $this->request->input('uid')) {
            $uid = (int) $uid;

            return [
                'online' => $this->onlineProvider->get($uid),
                'clients' => $this->clientProvider->size($uid),
            ];
        }

        return $this->addon->getMonitors();
    }
}
