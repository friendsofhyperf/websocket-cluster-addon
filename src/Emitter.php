<?php

declare(strict_types=1);
/**
 * This file is part of websocket-connection.
 *
 * @link     https://github.com/friendofhyperf/websocket-connection
 * @document https://github.com/friendofhyperf/websocket-connection/blob/main/README.md
 * @contact  huangdijia@gmail.com
 * @license  https://github.com/friendofhyperf/websocket-connection/blob/main/LICENSE
 */
namespace FriendsOfHyperf\WebsocketConnection;

use Psr\Container\ContainerInterface;

class Emitter
{
    /**
     * @var Server
     */
    private $server;

    public function __construct(ContainerInterface $container)
    {
        $this->server = $container->get(Server::class);
    }

    public function emit(int $uid, string $message): void
    {
        $this->server->publish(serialize([$uid, $message]));
    }

    public function broadcast(string $message): void
    {
        $this->emit(0, $message);
    }
}
