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
    'connections' => [
        'prefix' => 'wsca:connections',
        'pool' => 'default',
        'table' => [
            'size' => 10240,
        ],
    ],
    'client' => [
        'prefix' => 'wsca:clients',
        'pool' => 'default',
        'auto_clear_up' => false,
    ],
    'node' => [
        'prefix' => 'wsca:nodes',
        'pool' => 'default',
    ],
    'subscriber' => [
        'channel' => 'wsca:channel',
        'pool' => 'default',
        'retry_interval' => 1000,
    ],
];
