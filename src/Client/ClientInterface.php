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
namespace FriendsOfHyperf\WebsocketClusterAddon\Client;

use FriendsOfHyperf\WebsocketClusterAddon\Status\StatusInterface;

interface ClientInterface
{
    public function add(int $fd, int|string $uid): void;

    public function renew(int $fd, int|string $uid): void;

    public function del(int $fd, int|string $uid): void;

    public function clients(int|string $uid): array;

    public function size(int|string $uid): int;

    public function clearUpExpired(): void;

    public function getOnlineStatus(int|string $uid): bool;

    /**
     * @param (int|string)[] $uids
     */
    public function multiGetOnlineStatus(array $uids): array;

    public function getStatusInstance(): ?StatusInterface;
}
