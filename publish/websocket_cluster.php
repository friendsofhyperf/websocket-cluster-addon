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
return [
    'client' => [
        'prefix' => 'wssa:clients',
        'pool' => 'default',
    ],
    'connections' => [
        'prefix' => 'wssa:connections',
        'pool' => 'default',
    ],
    'online' => [
        'prefix' => 'wssa:online',
        'pool' => 'default',
    ],
    'server' => [
        'prefix' => 'wssa:servers',
        'pool' => 'default',
    ],
    'subscriber' => [
        'channel' => 'wssa:channel',
        'pool' => 'default',
        'retry_interval' => 1000,
    ],
];
