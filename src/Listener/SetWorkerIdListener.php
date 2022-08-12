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

use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;

#[Listener]
class SetWorkerIdListener implements ListenerInterface
{
    public function __construct(protected StdoutLoggerInterface $logger, private Server $server)
    {
    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            AfterWorkerStart::class,
        ];
    }

    /**
     * @param AfterWorkerStart $event
     */
    public function process(object $event): void
    {
        $this->server->setWorkerId($event->workerId);
        $this->logger->info(sprintf('[WebsocketClusterAddon] @%s #%s initialized by %s', $this->server->getServerId(), $event->workerId, self::class));
    }
}
