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

use Hyperf\Context\Context;

class Runner
{
    public const RUNNING_IN_LISTENER = 'context.running_in_listener';

    public static function setRunningInListener(): void
    {
        Context::set(self::RUNNING_IN_LISTENER, 1);
    }

    public static function isRunningInListener(): bool
    {
        return Context::has(self::RUNNING_IN_LISTENER);
    }
}
