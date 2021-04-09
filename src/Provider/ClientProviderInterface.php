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
namespace FriendsOfHyperf\WebsocketClusterAddon\Provider;

interface ClientProviderInterface
{
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
    public function renew($uid): void;

    /**
     * @param int|string $uid
     */
    public function size($uid): int;

    public function clearUpExpired(): void;

    public function flush(string $serverId = null): void;
}
