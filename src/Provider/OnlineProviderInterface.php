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

interface OnlineProviderInterface
{
    /**
     * @param int|string $uid
     */
    public function add($uid): void;

    /**
     * @param int|string $uid
     */
    public function del($uid): void;

    /**
     * @param array|int $uid
     */
    public function renew($uid): void;

    /**
     * @param int|string $uid
     */
    public function get($uid): bool;

    /**
     * @param int[]|string[] $uid
     */
    public function multiGet(array $uids): array;

    public function clearUpExpired(): void;
}
