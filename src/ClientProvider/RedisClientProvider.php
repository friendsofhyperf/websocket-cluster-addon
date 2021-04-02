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
namespace FriendsOfHyperf\WebsocketClusterAddon\ClientProvider;

use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\Parallel;
use Psr\Container\ContainerInterface;

class RedisClientProvider implements ClientProviderInterface
{
    protected $connection = 'default';

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $prefix = 'wsc:clients';

    /**
     * @var string
     */
    protected $serverId;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var Server
     */
    protected $server;

    public function __construct(ContainerInterface $container)
    {
        $this->redis = $container->get(RedisFactory::class)->get($this->connection);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->server = $container->get(Server::class);
        $this->serverId = $this->server->getServerId();
    }

    public function add(int $fd, int $uid): void
    {
        $key = $this->getKey($uid);
        $this->redis->sAdd($key, $fd);
        $this->redis->expire($key, 172800);
    }

    public function del(int $fd, int $uid): void
    {
        $this->redis->sRem($this->getKey($uid), $fd);
    }

    public function size(int $uid): int
    {
        $servers = $this->server->all();
        $parallel = new Parallel();

        foreach ($servers as $serverId) {
            $parallel->add(function () use ($serverId, $uid) {
                return $this->redis->sCard($this->getKey($uid, $serverId));
            });
        }

        $result = $parallel->wait();

        return array_sum(array_values($result));
    }

    public function flush(string $serverId = null): void
    {
        $keys = $this->redis->keys($this->getKey('*', $serverId));
        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }

    protected function getKey($uid, string $serverId = null): string
    {
        return join(':', [
            $this->prefix,
            $serverId ?? $this->serverId,
            $uid,
        ]);
    }
}
