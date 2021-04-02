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
namespace FriendsOfHyperf\WebsocketClusterAddon;

use Psr\Container\ContainerInterface;

class Emitter
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function emit(int $uid, string $message): void
    {
        /** @var Server $server */
        $server = $this->container->get(Server::class);
        $server->publish(serialize([$uid, $message]));
    }

    public function broadcast(string $message): void
    {
        $this->emit(0, $message);
    }
}
