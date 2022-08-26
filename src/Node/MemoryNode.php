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
use FriendsOfHyperf\WebsocketClusterAddon\PipeMessage;
use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Swoole\Server as SwooleServer;

class MemoryNode implements NodeInterface
{
    protected array $users = [];

    protected array $connections = [];

    public function __construct(protected ContainerInterface $container, protected StdoutLoggerInterface $logger)
    {
    }

    public function add(int $fd, $uid): void
    {
        $this->overrideUserConnections($uid, fn ($fds) => $fds[] = $fd);

        $this->connections[] = $fd;

        if (! Context::get(self::FROM_WORKER_ID)) {
            $this->sendPipeMessage($fd, $uid, __FUNCTION__);
        }
    }

    public function del(int $fd, $uid): void
    {
        $this->overrideUserConnections($uid, function ($fds) use ($fd) {
            $index = array_search($fd, $fds);
            if ($index !== false) {
                unset($fds[$index]);
            }
            return $fds;
        });

        $index = array_search($fd, $this->connections);
        if ($index !== false) {
            unset($this->connections[$index]);
        }

        if (! Context::get(self::FROM_WORKER_ID)) {
            $this->sendPipeMessage($fd, $uid, __FUNCTION__);
        }
    }

    public function users(): int
    {
        return count($this->users);
    }

    public function clients($uid = null): array
    {
        if (! $uid) {
            return $this->connections;
        }

        if (! isset($this->users[$uid])) {
            $this->users[$uid] = [];
        }

        return $this->users[$uid];
    }

    public function size($uid = null): int
    {
        return count($this->users[$uid] ?? []);
    }

    public function flush(?string $serverId = null): void
    {
    }

    protected function getSwooleServer(): SwooleServer
    {
        return $this->container->get(SwooleServer::class);
    }

    protected function getServer(): Server
    {
        return $this->container->get(Server::class);
    }

    protected function sendPipeMessage(int $fd, $uid, string $method = ''): void
    {
        $isAdd = $method == 'add';
        $swooleServer = $this->getSwooleServer();
        $workerCount = $swooleServer->setting['worker_num'] - 1;
        $currentWorkerId = $this->getServer()->getWorkerId();

        for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
            if ($workerId !== $currentWorkerId) {
                $swooleServer->sendMessage(new PipeMessage($fd, $uid, $isAdd), $workerId);
                $this->logger->debug(sprintf('[WebsocketClusterAddon] Let Worker.%s try to %s.', $workerId, $fd));
            }
        }
    }

    protected function overrideUserConnections($uid, Closure $callback): array
    {
        return $this->users[$uid] = $callback($this->users[$uid] ?? []);
    }
}
