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
namespace FriendsOfHyperf\WebsocketClusterAddon\Event;

class StatusChanged
{
    /**
     * @var int|string
     */
    public $uid;

    /**
     * @var int
     */
    public $status;

    public function __construct($uid, int $status)
    {
        $this->uid = $uid;
        $this->status = $status;
    }
}
