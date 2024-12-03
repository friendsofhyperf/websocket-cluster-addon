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
        'prefix' => 'wsca:client',
        'pool' => 'default',
        'table' => [
            'size' => 10240,
        ],
    ],
    'node' => [
        'prefix' => 'wsca:node',
        'pool' => 'default',
        'table' => [
            'size' => 10240,
        ],
    ],
    'subscriber' => [
        'channel' => 'wsca:channel',
        'pool' => 'default',
        'retry_interval' => 1000,
    ],

    'path' => '/websocket-cluster',
    'server' => 'http',
    'middlewares' => [],
];
