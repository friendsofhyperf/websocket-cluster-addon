<?php

declare(strict_types=1);
/**
 * This file is part of websocket-connection.
 *
 * @link     https://github.com/friendofhyperf/websocket-connection
 * @document https://github.com/friendofhyperf/websocket-connection/blob/main/README.md
 * @contact  huangdijia@gmail.com
 * @license  https://github.com/friendofhyperf/websocket-connection/blob/main/LICENSE
 */
namespace FriendsOfHyperf\WebsocketConnection;

class PipeMessage
{
    /**
     * @var int
     */
    public $fd;

    /**
     * @var int
     */
    public $uid;

    /**
     * @var bool
     */
    public $isAdd;

    /**
     * @var int
     */
    public $fromWorkerId;

    public function __construct(int $fd, int $uid, bool $isAdd = true, int $fromWorkerId = 0)
    {
        $this->fd = $fd;
        $this->uid = $uid;
        $this->isAdd = $isAdd;
        $this->fromWorkerId = $fromWorkerId;
    }
}
