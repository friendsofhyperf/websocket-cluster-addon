<?php

declare(strict_types=1);
/**
 * This file is part of websocket-connection.
 *
 * @link     https://github.com/friendofhyperf/websocket-connection
 * @document https://github.com/friendofhyperf/websocket-connection/blob/main/README.md
 * @contact  huangdijia@gmail.com
 * @license  https://github.com/friendofhyperf/websocket-connection/blob/main/LICENSE
 */
namespace FriendsOfHyperf\WebsocketConnection\Connection;

use FriendsOfHyperf\WebsocketConnection\PipeMessage;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Swoole\Server;

class MemoryConnection implements ConnectionInterface
{
    /**
     * @var MemoryConnector[]
     */
    protected $connections = [];

    /**
     * @var int
     */
    protected $workerId;

    /**
     * @var StdoutLoggerInterface
     */
    private $logger;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function add(int $fd, int $uid): void
    {
        $this->getConnector($uid)->add($fd);
        $this->getConnector(0)->add($fd);

        if (! Context::get(self::FROM_WORKER_ID)) {
            $this->sendPipeMessage($fd, $uid, __FUNCTION__);
        }
    }

    public function del(int $fd, int $uid): void
    {
        $this->getConnector($uid)->del($fd);
        $this->getConnector(0)->del($fd);

        if (! Context::get(self::FROM_WORKER_ID)) {
            $this->sendPipeMessage($fd, $uid, __FUNCTION__);
        }
    }

    public function size(int $uid): int
    {
        return $this->getConnector($uid)->size();
    }

    public function all(int $uid = 0): array
    {
        return $this->getConnector($uid)->all();
    }

    public function getConnector(int $uid): MemoryConnector
    {
        if (! isset($this->connections[$uid])) {
            $this->connections[$uid] = make(MemoryConnector::class);
        }

        return $this->connections[$uid];
    }

    public function flush(): void
    {
    }

    protected function getServer(): Server
    {
        return $this->container->get(Server::class);
    }

    protected function sendPipeMessage(int $fd, int $uid, string $method = ''): void
    {
        $server = $this->getServer();
        $workerCount = $server->setting['worker_num'] - 1;
        $isAdd = $method == 'add';

        for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
            if ($workerId !== $this->workerId) {
                $server->sendMessage(new PipeMessage($fd, $uid, $isAdd), $workerId);
                $this->logger->debug("[WebSocketConnection] Let Worker.{$workerId} try to {$fd}.");
            }
        }
    }
}
