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

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Mix\Redis\Subscribe\Subscriber;
use Psr\Container\ContainerInterface;
use RuntimeException;

class MixSubscriber implements SubscriberInterface
{
    /**
     * @var Subscriber
     */
    protected $sub;

    /**
     * @var string
     */
    protected $redisPool = 'default';

    /**
     * @var StdoutLoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->sub = value(function () use ($container) {
            $redis = $container->get(RedisFactory::class)->get($this->redisPool);
            $host = $redis->getHost();
            $port = $redis->getPort();
            $pass = $redis->getAuth();

            try {
                $sub = new Subscriber($host, $port, $pass ?? '', 5);
                defer(function () use ($sub) {
                    $sub->close();
                });
                return $sub;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    public function subscribe($channel, callable $callback): void
    {
        $sub = $this->sub;

        $sub->subscribe($channel);
        $chan = $sub->channel();

        Coroutine::create(function () use ($sub) {
            CoordinatorManager::until(Constants::WORKER_EXIT)->yield();
            $sub->close();
        });

        while (true) {
            $payload = $chan->pop();

            if (empty($payload)) { // 手动close与redis异常断开都会导致返回false
                if (! $sub->closed) {
                    throw new RuntimeException('Redis subscriber disconnected from Redis.');
                }
                break;
            }

            Coroutine::create(function () use ($payload, $callback) {
                $callback($payload);
                $this->logger->debug(sprintf('[WebsocketClusterAddon] %s by %s', json_encode(unserialize($payload), JSON_UNESCAPED_UNICODE), __CLASS__));
            });
        }
    }
}
