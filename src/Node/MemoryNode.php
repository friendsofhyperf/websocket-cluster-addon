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

use Closure;
use FriendsOfHyperf\IpcBroadcaster\Contract\BroadcasterInterface;
use FriendsOfHyperf\WebsocketClusterAddon\BroadcastNodePipeMessage;
use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Swoole\Server as SwooleServer;

class MemoryNode implements NodeInterface
{
    /**
     * @var array<int|string, array<int>>
     */
    protected array $users = [];

    /**
     * @var array<int>
     */
    protected array $connections = [];

    public function __construct(
        protected ContainerInterface $container,
        protected BroadcasterInterface $broadcaster,
        protected StdoutLoggerInterface $logger
    ) {}

    public function add(int $fd, int|string $uid, bool $onPipeMessage = false): void
    {
        $this->overrideUserConnections($uid, function ($fds) use ($fd) {
            if (! in_array($fd, $fds)) {
                $fds[] = $fd;
            }
            return $fds;
        });

        $this->overrideGlobalConnections(function ($fds) use ($fd) {
            if (! in_array($fd, $fds)) {
                $fds[] = $fd;
            }
            return $fds;
        });

        if (! $onPipeMessage) {
            $this->broadcaster->broadcast(new BroadcastNodePipeMessage($fd, $uid, true));
        }
    }

    public function del(int $fd, int|string $uid, bool $onPipeMessage = false): void
    {
        $this->overrideUserConnections($uid, function ($fds) use ($fd) {
            $index = array_search($fd, $fds);
            if ($index !== false) {
                unset($fds[$index]);
            }
            return $fds;
        });

        $this->overrideGlobalConnections(function ($fds) use ($fd) {
            $index = array_search($fd, $fds);
            if ($index !== false) {
                unset($fds[$index]);
            }
            return $fds;
        });

        if (! $onPipeMessage) {
            $this->broadcaster->broadcast(new BroadcastNodePipeMessage($fd, $uid, false));
        }
    }

    public function users(): int
    {
        return count($this->users);
    }

    public function clients(null|int|string $uid = null): array
    {
        if (! $uid) {
            return $this->connections;
        }

        if (! isset($this->users[$uid])) {
            $this->users[$uid] = [];
        }

        return $this->users[$uid];
    }

    public function size(null|int|string $uid = null): int
    {
        return count($this->users[$uid] ?? []);
    }

    public function flush(?string $serverId = null): void {}

    protected function getSwooleServer(): SwooleServer
    {
        return $this->container->get(SwooleServer::class);
    }

    protected function getServer(): Server
    {
        return $this->container->get(Server::class);
    }

    /**
     * @param Closure(int[]):array $callback
     */
    protected function overrideUserConnections(int|string $uid, Closure $callback): void
    {
        $this->users[$uid] = $callback(
            (array) ($this->users[$uid] ?? [])
        );

        if (empty($this->users[$uid])) {
            unset($this->users[$uid]);
        }
    }

    /**
     * @param Closure(int[]):array $callback
     */
    protected function overrideGlobalConnections(Closure $callback): void
    {
        $this->connections = $callback($this->connections);
    }

    private function sendPipeMessage(int $fd, int|string $uid, string $method = ''): void
    {
        $isAdd = $method == 'add';
        $swooleServer = $this->getSwooleServer();
        $workerCount = $swooleServer->setting['worker_num'] - 1;
        $currentWorkerId = $this->getServer()->getWorkerId();

        for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
            if ($workerId !== $currentWorkerId) {
                $swooleServer->sendMessage(new BroadcastNodePipeMessage($fd, $uid, $isAdd), $workerId);
                $this->logger->debug(sprintf('[WebsocketClusterAddon] Let Worker.%s try to %s.', $workerId, $fd));
            }
        }
    }
}
