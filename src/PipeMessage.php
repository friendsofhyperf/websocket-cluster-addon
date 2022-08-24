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
namespace FriendsOfHyperf\WebsocketClusterAddon;

class PipeMessage
{
    public int $fd;

    /**
     * @var int|string
     */
    public $uid;

    public bool $isAdd;

    /**
     * @param int|string $uid
     */
    public function __construct(int $fd, $uid, bool $isAdd = true)
    {
        $this->fd = $fd;
        $this->uid = $uid;
        $this->isAdd = $isAdd;
    }
}
