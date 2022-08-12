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

#[Listener]
class InitNodeListener implements ListenerInterface
{
    public function __construct(protected ConfigInterface $config, protected StdoutLoggerInterface $logger, protected NodeInterface $node)
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
        if ($this->node instanceof TableNode) {
            $size = (int) $this->config->get('websocket_cluster.node.table.size', 10240);
            $this->node->initTable($size);
            $this->logger->info(sprintf('[WebsocketClusterAddon] table initialized by %s', self::class));
        }
    }
}
