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

use FriendsOfHyperf\WebsocketClusterAddon\Client\ClientInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Client\TableClient;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;

#[Listener]
class InitClientListener implements ListenerInterface
{
    public function __construct(
        protected ConfigInterface $config,
        protected StdoutLoggerInterface $logger,
        protected ClientInterface $client
    ) {
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
        if ($this->client instanceof TableClient) {
            $size = (int) $this->config->get('websocket_cluster.client.table.size', 10240);
            $this->client->initTable($size);
            $this->logger->info(sprintf('[WebsocketClusterAddon] table initialized by %s', self::class));
        }
    }
}
