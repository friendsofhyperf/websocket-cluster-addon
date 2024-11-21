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

namespace FriendsOfHyperf\WebsocketClusterAddon\Listener;

use FriendsOfHyperf\WebsocketClusterAddon\Context;
use FriendsOfHyperf\WebsocketClusterAddon\Node\MemoryNode;
use FriendsOfHyperf\WebsocketClusterAddon\Node\NodeInterface;
use FriendsOfHyperf\WebsocketClusterAddon\PipeMessage;
use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnPipeMessage;
use Hyperf\Process\Event\PipeMessage as UserProcessPipMessage;

class OnPipeMessageListener implements ListenerInterface
{
    public function __construct(protected Server $server, protected NodeInterface $node, protected StdoutLoggerInterface $logger) {}

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
     * @param OnPipeMessage|UserProcessPipMessage $event
     */
    public function process(object $event): void
    {
        if (! $this->node instanceof MemoryNode) {
            return;
        }

        if (property_exists($event, 'data') && $event->data instanceof PipeMessage) {
            /** @var PipeMessage $data */
            $data = $event->data;

            $fd = $data->fd;
            $uid = $data->uid;
            $isAdd = $data->isAdd;

            Context::setCurrentWorkerId($event->fromWorkerId);

            if ($isAdd) {
                $this->node->add($fd, $uid);
            } else {
                $this->node->del($fd, $uid);
            }

            $this->logger->debug(
                sprintf(
                    '[WebsocketClusterAddon] @%s #%s [%s] is %s by %s listener.',
                    $this->server->getServerId(),
                    $this->server->getWorkerId(),
                    $fd,
                    $isAdd ? 'added' : 'deleted',
                    self::class
                )
            );
        }
    }
}
