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

interface NodeInterface
{
    public const FROM_WORKER_ID = 'FROM_WORKER_ID';

    public function add(int $fd, int|string $uid): void;

    public function del(int $fd, int|string $uid): void;

    public function users(): int;

    public function clients(null|int|string $uid = null): array;

    public function size(null|int|string $uid = null): int;

    public function flush(?string $serverId = null): void;
}
