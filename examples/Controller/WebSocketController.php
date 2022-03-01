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
namespace App\Controller;

use FriendsOfHyperf\WebsocketClusterAddon\Client\ClientInterface;
use FriendsOfHyperf\WebsocketClusterAddon\Node\NodeInterface;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\WebSocketServer\Context;
use Hyperf\WebSocketServer\Sender;
use Psr\Container\ContainerInterface;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var Sender
     */
    private $sender;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->client = $container->get(ClientInterface::class);
        $this->node = $container->get(NodeInterface::class);
        $this->sender = $container->get(Sender::class);
    }

    public function onOpen($server, $request): void
    {
        $fd = (int) $request->fd;
        $uid = 1;

        Context::set('uid', $uid);

        $this->node->add($fd, $uid);
        $this->client->add($fd, $uid);

        $this->sender->push($fd, 'Welcome!');
    }

    public function onMessage($server, $frame): void
    {
        $uid = Context::get('uid');
        $fd = (int) $frame->fd;

        if ($frame->data == 'ping') {
            $this->client->renew($fd, $uid);
            $this->sender->push($fd, 'pong!');
        }
    }

    public function onClose($server, int $fd, int $reactorId): void
    {
        $uid = Context::get('uid');

        $this->node->add($fd, $uid);
        $this->client->add($fd, $uid);

        $this->sender->push($fd, 'Bye!');
    }
}
