<?php

declare(strict_types=1);
/**
 * This file is part of websocket-connection.
 *
 * @link     https://github.com/friendofhyperf/websocket-connection
 * @document https://github.com/friendofhyperf/websocket-connection/blob/main/README.md
 * @contact  huangdijia@gmail.com
 * @license  https://github.com/friendofhyperf/websocket-connection/blob/main/LICENSE
 */
namespace FriendsOfHyperf\ConfigAnyway;

use FriendsOfHyperf\WebsocketConnection\Connection\ConnectionInterface;
use FriendsOfHyperf\WebsocketConnection\Connection\MemoryConnection;
use FriendsOfHyperf\WebsocketConnection\Listener\InitConnectionListener;
use FriendsOfHyperf\WebsocketConnection\Listener\OnPipeMessageListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        defined('BASE_PATH') or define('BASE_PATH', __DIR__ . '/../../../');

        return [
            'dependencies' => [
                ConnectionInterface::class => MemoryConnection::class,
            ],
            'processes' => [],
            'listeners' => [
                InitConnectionListener::class,
                OnPipeMessageListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [],
        ];
    }
}
