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

namespace FriendsOfHyperf\WebsocketClusterAddon;

use Hyperf\Context\Context as Ctx;

class Context
{
    public const CURRENT_WORKER_ID = 'context.current_worker_id';

    public static function setCurrentWorkerId(?int $workerId): void
    {
        Ctx::set(self::CURRENT_WORKER_ID, $workerId);
    }

    public static function getCurrentWorkerId(): ?int
    {
        return Ctx::get(self::CURRENT_WORKER_ID, 0);
    }
}
