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
namespace FriendsOfHyperf\WebsocketClusterAddon\ClientProvider;

interface ClientProviderInterface
{
    public function add(int $fd, int $uid): void;

    public function del(int $fd, int $uid): void;

    public function size(int $uid): int;

    public function flush(string $serverId = null): void;
}
