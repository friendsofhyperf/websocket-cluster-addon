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

namespace FriendsOfHyperf\WebsocketClusterAddon\Status;

interface StatusInterface
{
    public function set(int|string $uid, bool $status): void;

    public function get(int|string $uid): bool;

    public function multiSet(array $uids, bool $status): void;

    /**
     * @return array<int, bool>
     */
    public function multiGet(array $uids): array;

    public function count(): int;
}
