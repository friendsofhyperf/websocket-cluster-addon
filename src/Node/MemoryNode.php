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
namespace FriendsOfHyperf\WebsocketClusterAddon\Node;

use FriendsOfHyperf\WebsocketClusterAddon\Adapter\MemoryAdapter;
use FriendsOfHyperf\WebsocketClusterAddon\PipeMessage;
use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Swoole\Server as SwooleServer;

class MemoryNode implements NodeInterface
{
    /**
     * @var MemoryAdapter[]
     */
    protected $adapters = [];

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function add(int $fd, $uid): void
    {
        $this->getAdapter($uid)->add($fd);
        $this->getAdapter(0)->add($fd);

        if (! Context::get(self::FROM_WORKER_ID)) {
            $this->sendPipeMessage($fd, $uid, __FUNCTION__);
        }
    }

    public function del(int $fd, $uid): void
    {
        $this->getAdapter($uid)->del($fd);
        $this->getAdapter(0)->del($fd);

        if (! Context::get(self::FROM_WORKER_ID)) {
            $this->sendPipeMessage($fd, $uid, __FUNCTION__);
        }
    }

    public function users(): int
    {
        return collect($this->adapters)
            ->reject(function (MemoryAdapter $adapter, $uid) {
                return $uid == 0 || $adapter->count() <= 0;
            })
            ->count();
    }

    public function clients($uid): array
    {
        return $this->getAdapter($uid)->toArray();
    }

    public function size($uid): int
    {
        return $this->getAdapter($uid)->count();
    }

    public function flush(?string $serverId = null): void
    {
    }

    protected function getAdapter($uid): MemoryAdapter
    {
        if (! isset($this->adapters[$uid])) {
            $this->adapters[$uid] = make(MemoryAdapter::class);
        }

        return $this->adapters[$uid];
    }

    protected function getSwooleServer(): SwooleServer
    {
        return $this->container->get(SwooleServer::class);
    }

    protected function getServer(): Server
    {
        return $this->container->get(Server::class);
    }

    /**
     * @param int|string $uid
     */
    protected function sendPipeMessage(int $fd, $uid, string $method = ''): void
    {
        $isAdd = $method == 'add';
        $swooleServer = $this->getSwooleServer();
        $workerCount = $swooleServer->setting['worker_num'] - 1;
        $currentWorkerId = $this->getServer()->getWorkerId();

        for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
            if ($workerId !== $currentWorkerId) {
                $swooleServer->sendMessage(new PipeMessage($fd, $uid, $isAdd), $workerId);
                $this->logger->debug("[WebsocketClusterAddon] Let Worker.{$workerId} try to {$fd}.");
            }
        }
    }
}
