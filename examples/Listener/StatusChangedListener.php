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
namespace App\Listener;

use FriendsOfHyperf\WebsocketClusterAddon\Emitter;
use FriendsOfHyperf\WebsocketClusterAddon\Event\StatusChanged;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Container\ContainerInterface;

/**
 * @Listener
 */
class StatusChangedListener implements ListenerInterface
{
    /**
     * @var Emitter
     */
    protected $emitter;

    public function __construct(ContainerInterface $container)
    {
        $this->emitter = $container->get(Emitter::class);
    }

    public function listen(): array
    {
        return [
            StatusChanged::class,
        ];
    }

    /**
     * @param StatusChanged $event
     */
    public function process(object $event)
    {
        $uid = $event->uid;
        $status = $event->status;
        $contacts = [];

        foreach ($contacts as $contactId) {
            $this->emitter->emit($contactId, "{$uid} current status is {$status}");
        }
    }
}
