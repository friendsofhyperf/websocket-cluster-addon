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

use FriendsOfHyperf\WebsocketClusterAddon\Client\ClientInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Node\NodeInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Subscriber\SubscriberInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coordinator\Timer;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Hyperf\WebSocketServer\Sender;
use Psr\Container\ContainerInterface;
use Throwable;

use function Hyperf\Collection\collect;
use function Hyperf\Support\make;
use function Hyperf\Support\retry;

class Server
{
    protected string $channel = 'wsca:channels';

    protected string $prefix = 'wsca:nodes';

    protected ?string $serverId = null;

    protected ?int $workerId = null;

    protected Redis $redis;

    protected Timer $timer;

    protected bool $stopped = false;

    /**
     * Milliseconds.
     */
    protected int $retryInterval = 1000;

    public function __construct(
        ContainerInterface $container,
        protected NodeInterface $node,
        protected ClientInterface $client,
        protected StdoutLoggerInterface $logger,
        protected Sender $sender
    ) {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $this->channel = $config->get('websocket_cluster.subscriber.channel', 'wsca:channels');
        $this->prefix = $config->get('websocket_cluster.node.prefix', 'wsca:nodes');
        $this->retryInterval = (int) $config->get('websocket_cluster.subscriber.retry_interval', 1000);
        $this->redis = $container->get(RedisFactory::class)->get($config->get('websocket_cluster.node.pool', 'default'));
        $this->timer = new Timer($logger);
    }

    public function setServerId(string $serverId): void
    {
        $this->serverId = $serverId;
    }

    public function getServerId(): ?string
    {
        return $this->serverId;
    }

    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }

    public function getWorkerId(): ?int
    {
        return $this->workerId;
    }

    public function start(): void
    {
        $this->subscribe();
        $this->keepalive();
        $this->clearUpExpired();
    }

    public function stop(): void
    {
        $this->stopped = true;
        $this->timer->clearAll();
    }

    public function broadcast(string $payload): void
    {
        [$uid, $message, $serverId] = unserialize($payload);

        if ($serverId) { // fix cannot send when executed by custom process
            Coroutine::create(function () use ($payload) {
                $this->doBroadcast($payload, true);
            });
        }

        $this->publish($this->getChannelKey(), $payload);
    }

    public function subscribe(): void
    {
        Coroutine::create(function () {
            retry(PHP_INT_MAX, function () {
                try {
                    // fix Uncaught Swoole\Error: API must be called in the coroutine when $subscriber instanceof \Mix\Redis\Subscribe\Subscriber
                    $subscriber = make(SubscriberInterface::class);

                    $subscriber->subscribe($this->getChannelKey(), function ($channel, $payload) {
                        $this->doBroadcast($payload);
                    });
                } catch (Throwable $e) {
                    $this->logger->error((string) $e);
                    throw $e;
                }
            }, $this->retryInterval);
        });
    }

    public function keepalive(): void
    {
        Coroutine::create(function () {
            $this->timer->tick(1, function () {
                // Keep server alive
                $this->redis->zAdd($this->getNodeKey(), time(), $this->serverId);

                // Sync server info
                $data = json_encode([
                    'node' => $this->getServerId(),
                    'users' => $this->node->users(),
                    'connections' => $this->node->size(0),
                ], JSON_UNESCAPED_UNICODE);
                $this->redis->hSet($this->getMonitorKey(), $this->getServerId(), $data);

                if (time() % 5 == 0) {
                    $this->logger->debug(sprintf('[WebsocketClusterAddon] @%s keepalive by %s', $this->serverId, self::class));
                }
            });
        });
    }

    public function clearUpExpired(): void
    {
        Coroutine::create(function () {
            $this->timer->tick(5, function () {
                // Clear up expired servers
                $this->clearUpExpiredNodes();

                // Clear up expired clients
                $this->client->clearUpExpired();
            });
        });
    }

    public function getNodes(): array
    {
        return $this->redis->zRangeByScore($this->getNodeKey(), '-inf', '+inf');
    }

    public function getMonitors(): array
    {
        return collect($this->redis->hGetAll($this->getMonitorKey()))
            ->transform(fn ($item) => json_decode($item, true, 512, JSON_THROW_ON_ERROR))
            ->values()
            ->toArray();
    }

    protected function clearUpExpiredNodes(): void
    {
        $start = '-inf';
        $end = (string) strtotime('-30 seconds');
        $expiredServers = $this->redis->zRangeByScore($this->getNodeKey(), $start, $end);

        if (! $expiredServers) {
            return;
        }

        $this->redis->zRem($this->getNodeKey(), ...$expiredServers);
        $this->redis->hDel($this->getMonitorKey(), ...$expiredServers);

        foreach ($expiredServers as $serverId) {
            $this->node->flush($serverId);
        }

        $this->logger->info(sprintf('[WebsocketClusterAddon] @%s clear up expired servers by %s', $this->serverId, self::class));
    }

    protected function publish(string $channel, string $payload): void
    {
        $this->redis->publish($channel, $payload);
    }

    protected function doBroadcast(string $payload, bool $isLocal = false): void
    {
        [$uid, $message, $serverId] = unserialize($payload);

        if (! $isLocal && $serverId == $this->serverId) {
            return;
        }

        $clients = $this->node->clients($uid);

        foreach ($clients as $fd) {
            $this->sender->push($fd, $message);
        }
    }

    protected function getChannelKey(): string
    {
        return $this->channel;
    }

    protected function getNodeKey(): string
    {
        return join(':', [$this->prefix]);
    }

    protected function getMonitorKey(): string
    {
        return join(':', [$this->prefix, 'monitoring']);
    }
}
