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

use FriendsOfHyperf\WebsocketConnection\Server;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractConnection implements ConnectionInterface
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $serverId;

    /**
     * @var int
     */
    protected $workerId;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(StdoutLoggerInterface::class);
        /** @var Server $server */
        $server = $container->get(Server::class);
        $this->serverId = $server->getServerId();
        $this->workerId = $server->getWorkerId();
    }
}
