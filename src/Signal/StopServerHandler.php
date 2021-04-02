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
namespace FriendsOfHyperf\WebsocketClusterAddon\Signal;

use FriendsOfHyperf\WebsocketClusterAddon\Connection\ConnectionInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;
use Psr\Container\ContainerInterface;

/**
 * @Signal(priority=-1)
 */
class StopServerHandler implements SignalHandlerInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

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
        $this->config = $container->get(ConfigInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->server = $container->get(Server::class);
    }

    public function listen(): array
    {
        return [
            [self::WORKER, SIGTERM],
            [self::WORKER, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        if ($signal !== SIGINT) {
            $time = $this->config->get('server.settings.max_wait_time', 5);
            sleep($time);
        }

        $this->server->setIsRunning(false);

        $this->logger->info(sprintf('[WebsocketClusterAddon] stopped by %s.', __CLASS__));
    }
}
