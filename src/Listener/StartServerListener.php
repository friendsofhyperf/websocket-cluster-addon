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
use Hyperf\Framework\Event\MainWorkerStart;

#[Listener]
class StartServerListener implements ListenerInterface
{
    public function __construct(protected StdoutLoggerInterface $logger, protected Server $server)
    {
    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            MainWorkerStart::class,
        ];
    }

    /**
     * @param MainWorkerStart $event
     */
    public function process(object $event): void
    {
        $this->server->start();
        $this->logger->info(sprintf('[WebsocketClusterAddon] @%s #%s started by %s', $this->server->getServerId(), $event->workerId, self::class));
    }
}
