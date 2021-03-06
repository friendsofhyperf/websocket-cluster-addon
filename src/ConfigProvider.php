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

class ConfigProvider
{
    public function __invoke(): array
    {
        defined('BASE_PATH') or define('BASE_PATH', __DIR__ . '/../../../');

        return [
            'dependencies' => [
                Client\ClientInterface::class => Client\RedisClient::class,
                Node\NodeInterface::class => Node\MemoryNode::class,
                Subscriber\SubscriberInterface::class => class_exists(\Mix\Redis\Subscriber\Subscriber::class) ? Subscriber\MixSubscriber::class : Subscriber\PhpRedisSubscriber::class,
            ],
            'processes' => [],
            'listeners' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for websocket-cluster-addon.',
                    'source' => __DIR__ . '/../publish/websocket_cluster.php',
                    'destination' => BASE_PATH . '/config/autoload/websocket_cluster.php',
                ],
            ],
        ];
    }
}
