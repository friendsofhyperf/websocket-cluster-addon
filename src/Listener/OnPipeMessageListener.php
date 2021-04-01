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
namespace FriendsOfHyperf\WebsocketConnection\Listener;

use FriendsOfHyperf\WebsocketConnection\Connection\ConnectionInterface;
use FriendsOfHyperf\WebsocketConnection\PipeMessage;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnPipeMessage;
use Hyperf\Process\Event\PipeMessage as UserProcessPipMessage;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;

/**
 * @Listener
 */
class OnPipeMessageListener implements ListenerInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var StdoutLoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->connection = $container->get(ConnectionInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            OnPipeMessage::class,
            UserProcessPipMessage::class,
        ];
    }

    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process(object $event)
    {
        if (property_exists($event, 'data') && $event->data instanceof PipeMessage) {
            /** @var PipeMessage $data */
            $data = $event->data;

            $fd = $data->fd;
            $uid = $data->uid;
            $isAdd = $data->isAdd;

            Context::set(ConnectionInterface::FROM_WORKER_ID, $event->fromWorkerId);

            if ($isAdd) {
                $this->connection->add($fd, $uid);
                $this->logger->debug(sprintf('[WebSocketConnection.%s][%s] is %s by %s listener.', $this->connection->getWorkerId(), $fd, 'added', __CLASS__));
            } else {
                $this->connection->del($fd, $uid);
                $this->logger->debug(sprintf('[WebSocketConnection.%s][%s] is %s by %s listener.', $this->connection->getWorkerId(), $fd, 'deleted', __CLASS__));
            }
        }
    }
}
