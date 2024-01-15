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

use function Hyperf\Support\make;

class SubscriberFactory
{
    public function __invoke()
    {
        $driver = class_exists(\Mix\Redis\Subscriber\Subscriber::class) ? MixSubscriber::class : PhpRedisSubscriber::class;

        return make($driver);
    }
}
