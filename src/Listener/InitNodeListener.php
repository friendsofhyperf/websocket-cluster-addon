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

use FriendsOfHyperf\WebsocketClusterAddon\Node\NodeInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Node\TableNode;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Psr\Container\ContainerInterface;

/**
 * @Listener
 */
class InitNodeListener implements ListenerInterface
{
    /**
     * @var StdoutLoggerInterface
     */
    private $logger;

    /**
     * @var TableNode
     */
    private $node;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->node = $container->get(NodeInterface::class);
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
        if ($this->node instanceof TableNode) {
            $size = (int) $this->config->get('websocket_cluster.connection.table.size', 10240);
            $this->node->initTable($size);
            $this->logger->info(sprintf('[WebsocketClusterAddon] table initialized by %s', __CLASS__));
        }
    }
}
