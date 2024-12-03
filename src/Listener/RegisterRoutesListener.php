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

namespace FriendsOfHyperf\WebsocketClusterAddon\Listener;

use FriendsOfHyperf\WebsocketClusterAddon\Controller\InfoController;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Router;

class RegisterRoutesListener implements ListenerInterface
{
    public function __construct(
        protected DispatcherFactory $dispatcherFactory, // Don't remove this line
        protected ConfigInterface $config
    ) {}

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $server = $this->config->get('websocket_cluster.server', 'http');
        $prefix = $this->config->get('websocket_cluster.path', '/websocket-cluster');
        $middlewares = $this->config->get('websocket_cluster.middlewares', []);

        Router::addServer($server, function () use ($prefix, $middlewares) {
            Router::addGroup($prefix, function () {
                Router::get('/info', InfoController::class . '@info');
            }, ['middleware' => $middlewares]);
        });
    }
}
