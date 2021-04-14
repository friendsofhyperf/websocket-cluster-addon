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
namespace FriendsOfHyperf\WebsocketClusterAddon\Node;

class MemoryConnector
{
    protected $container = [];

    public function add(int $fd): void
    {
        $this->container[$fd] = true;
    }

    public function del(int $fd): void
    {
        unset($this->container[$fd]);
    }

    public function size(): int
    {
        return count($this->clients());
    }

    public function all(): array
    {
        return $this->clients();
    }

    public function clients(): array
    {
        return array_keys($this->container);
    }
}
