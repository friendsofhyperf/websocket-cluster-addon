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
use FriendsOfHyperf\WebsocketClusterAddon\Status\StatusInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Status\TableStatus;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Psr\Container\ContainerInterface;

/**
 * @Listener
 */
class InitClientListener implements ListenerInterface
{
    private StdoutLoggerInterface $logger;

    /**
     * @var TableClient
     */
    private ClientInterface $client;

    /**
     * @var TableStatus
     */
    private StatusInterface $status;

    private ConfigInterface $config;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->client = $container->get(ClientInterface::class);
        $this->status = $container->get(StatusInterface::class);
        $this->config = $container->get(ConfigInterface::class);
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
    public function process(object $event)
    {
        if ($this->client instanceof TableClient) {
            $size = (int) $this->config->get('websocket_cluster.client.table.size', 10240);
            $this->client->initTable($size);
            $this->logger->info(sprintf('[WebsocketClusterAddon] client table initialized by %s', self::class));
        }

        if ($this->status instanceof TableStatus) {
            $size = (int) $this->config->get('websocket_cluster.client.table.size', 10240);
            $this->status->initTable($size);
            $this->logger->info(sprintf('[WebsocketClusterAddon] status table initialized by %s', self::class));
        }
    }
}
