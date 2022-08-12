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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerInterface;

class PhpRedisSubscriber implements SubscriberInterface
{
    protected Redis $redis;

    public function __construct(ContainerInterface $container, ConfigInterface $config, protected StdoutLoggerInterface $logger)
    {
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.subscriber.pool', 'default'));
    }

    public function subscribe($channel, callable $callback): void
    {
        $this->redis->subscribe((array) $channel, function ($redis, $channel, $payload) use ($callback) {
            Coroutine::create(function () use ($channel, $payload, $callback) {
                $callback($channel, $payload);
                $this->logger->debug(
                    sprintf(
                        '[WebsocketClusterAddon] channel: %s, payload: %s by %s',
                        $channel,
                        json_encode(unserialize($payload), JSON_UNESCAPED_UNICODE),
                        self::class
                    )
                );
            });
        });
    }
}
