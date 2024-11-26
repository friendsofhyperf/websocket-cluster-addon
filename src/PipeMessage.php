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

namespace FriendsOfHyperf\WebsocketClusterAddon;

use FriendsOfHyperf\IpcBroadcaster\IpcMessage;
use FriendsOfHyperf\WebsocketClusterAddon\Node\MemoryNode;
use FriendsOfHyperf\WebsocketClusterAddon\Node\NodeInterface;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;

class PipeMessage extends IpcMessage
{
    public function __construct(
        public int $fd,
        public int|string $uid,
        public bool $isAdd = true
    ) {}

    public function handle(): void
    {
        $fd = $this->fd;
        $uid = $this->uid;
        $isAdd = $this->isAdd;
        $node = $this->get(NodeInterface::class);
        $server = $this->get(Server::class);
        $logger = $this->get(StdoutLoggerInterface::class);

        if (! $node || ! $node instanceof MemoryNode) {
            return;
        }

        if ($isAdd) {
            $node->add($fd, $uid, true);
        } else {
            $node->del($fd, $uid, true);
        }

        $logger?->debug(
            sprintf(
                '[WebsocketClusterAddon] @%s #%s [%s] is %s by %s listener.',
                $server?->getServerId(),
                $server?->getWorkerId(),
                $fd,
                $isAdd ? 'added' : 'deleted',
                self::class
            )
        );
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return null|T
     */
    public function get(string $class)
    {
        if (! ApplicationContext::hasContainer()) {
            return null;
        }

        $container = ApplicationContext::getContainer();

        if (! $container->has($class)) {
            return null;
        }

        return $container->get($class);
    }
}
