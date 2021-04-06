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
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerInterface;

class PhpRedisSubscriber implements SubscriberInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var StdoutLoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.subscriber.pool', 'default'));
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function subscribe($channel, callable $callback): void
    {
        $this->redis->subscribe((array) $channel, function ($redis, $channel, $payload) use ($callback) {
            Coroutine::create(function () use ($payload, $callback) {
                $callback($payload);
                $this->logger->debug(sprintf('[WebsocketClusterAddon] %s by %s', json_encode(unserialize($payload), JSON_UNESCAPED_UNICODE), __CLASS__));
            });
        });
    }
}
