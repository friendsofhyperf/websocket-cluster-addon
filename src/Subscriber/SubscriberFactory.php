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

namespace FriendsOfHyperf\WebsocketClusterAddon\Subscriber;

use FriendsOfHyperf\Redis\Subscriber\Subscriber;
use Mix\Redis\Subscriber\Subscriber as MixRedisSubscriber;
use Redis;
use RuntimeException;

use function Hyperf\Support\make;

class SubscriberFactory
{
    public function __invoke()
    {
        return match (true) {
            class_exists(Subscriber::class) => make(CoroutineSubscriber::class),
            class_exists(MixRedisSubscriber::class) => make(MixSubscriber::class),
            class_exists(Redis::class) => make(PhpRedisSubscriber::class),
            default => throw new RuntimeException('No redis driver found.'),
        };
    }
}
