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
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Utils\Str;
use Psr\Container\ContainerInterface;

#[Listener]
class SetServerIdListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container, protected StdoutLoggerInterface $logger, protected Server $server)
    {
    }

    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
        ];
    }

    /**
     * @param BeforeMainServerStart $event
     */
    public function process(object $event): void
    {
        $serverId = Str::slug(gethostname() ?: uniqid());
        $this->server->setServerId($serverId);
        $this->logger->info(sprintf('[WebsocketClusterAddon] @%s #%s serverId initialized by %s', -1, $serverId, self::class));
    }
}
