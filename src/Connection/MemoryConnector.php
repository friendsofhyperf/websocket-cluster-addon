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
namespace FriendsOfHyperf\WebsocketConnection\Connection;

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
        return count($this->all());
    }

    public function all(): array
    {
        return array_keys($this->container);
    }
}
