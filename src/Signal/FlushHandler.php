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
namespace FriendsOfHyperf\WebsocketConnection\Signal;

use FriendsOfHyperf\WebsocketConnection\ConnectionInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;
use Psr\Container\ContainerInterface;

/**
 * @Signal(priority=-1)
 */
class FlushHandler implements SignalHandlerInterface
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

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $this->connection = $container->get(ConnectionInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
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

        $this->connection->flush();
        $this->logger->info(sprintf('[WebSocketConnection] flushed by %s.', __CLASS__));
    }
}
