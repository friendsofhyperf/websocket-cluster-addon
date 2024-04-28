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

use FriendsOfHyperf\Redis\Subscriber\Message;
use FriendsOfHyperf\Redis\Subscriber\Subscriber;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

use function Hyperf\Collection\value;

class CoroutineSubscriber implements SubscriberInterface
{
    protected Subscriber $subscriber;

    public function __construct(ContainerInterface $container, protected StdoutLoggerInterface $logger)
    {
        $this->subscriber = value(function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            /** @var Redis $redis */
            $redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.subscriber.pool', 'default'));
            $host = $redis->getHost();
            $port = $redis->getPort();
            $pass = $redis->getAuth();

            try {
                return new Subscriber($host, $port, $pass ?? '', 5);
            } catch (Throwable) {
                return null;
            }
        });
    }

    public function subscribe($channel, callable $callback): void
    {
        $sub = $this->subscriber;

        $sub->subscribe($channel);
        $chan = $sub->channel();

        Coroutine::create(function () use ($sub) {
            CoordinatorManager::until(Constants::WORKER_EXIT)->yield();
            $sub->close();
        });

        while (true) {
            /** @var Message $data */
            $data = $chan->pop();

            if (empty($data)) { // 手动close与redis异常断开都会导致返回false
                if (! $sub->closed) {
                    throw new RuntimeException('Redis subscriber disconnected from Redis.');
                }
                break;
            }

            Coroutine::create(function () use ($callback, $data) {
                $callback($data->channel, $data->payload);
                $this->logger->debug(
                    sprintf(
                        '[WebsocketClusterAddon] channel: %s, payload: %s by %s',
                        $data->channel,
                        json_encode(unserialize($data->payload), JSON_UNESCAPED_UNICODE),
                        self::class
                    )
                );
            });
        }
    }
}
