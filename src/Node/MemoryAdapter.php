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

use Countable;

class MemoryAdapter implements Countable
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

    public function count(): int
    {
        return count($this->container);
    }

    public function clients(): array
    {
        return array_keys($this->container);
    }
}
