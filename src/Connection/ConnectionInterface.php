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
namespace FriendsOfHyperf\WebsocketClusterAddon\Connection;

interface ConnectionInterface
{
    const FROM_WORKER_ID = 'FROM_WORKER_ID';

    /**
     * @param int|string $uid
     */
    public function add(int $fd, $uid): void;

    /**
     * @param int|string $uid
     */
    public function del(int $fd, $uid): void;

    /**
     * @param int|string $uid
     */
    public function size($uid): int;

    public function users(): int;

    /**
     * @param int|string $uid
     */
    public function clients($uid): array;

    /**
     * @param int|string $uid
     * @deprecated
     */
    public function all($uid): array;

    public function flush(?string $serverId = null): void;
}
