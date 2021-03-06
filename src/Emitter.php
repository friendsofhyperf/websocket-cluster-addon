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

    /**
     * @param int|string $uid
     * @param array|object|string $data
     */
    public function emit($uid, $data): void
    {
        $data = $this->formatData($data);
        /** @var Server $server */
        $server = $this->container->get(Server::class);
        // Set serverId for null when executed on custom process
        $serverId = $server->getWorkerId() ? $server->getServerId() : null;
        $server->broadcast(serialize([$uid, $data, $serverId]));
    }

    /**
     * @param array|object|string $data
     */
    public function broadcast($data): void
    {
        $this->emit(0, $data);
    }

    /**
     * @param array|object|string $data
     */
    protected function formatData($data): string
    {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        } elseif (is_callable([$data, '__toString'])) {
            $data = $data->__toString();
        } elseif (is_callable([$data, 'toArray'])) {
            $data = json_encode($data->toArray(), JSON_UNESCAPED_UNICODE);
        }

        return $data;
    }
}
