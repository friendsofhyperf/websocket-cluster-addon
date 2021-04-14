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
namespace FriendsOfHyperf\WebsocketClusterAddon\Node;

use Countable;
use Hyperf\Utils\Contracts\Arrayable;
use Swoole\Table;

class TableConnector implements Countable, Arrayable
{
    /**
     * @var array
     */
    private $data = [];

    public function __construct(string $data)
    {
        $data = unserialize($data);

        if (is_array($data)) {
            $this->data = $data;
        }
    }

    public function __toString(): string
    {
        return serialize($this->data);
    }

    public static function make(Table $table, string $uid, string $field = 'fds'): self
    {
        $json = (string) ($table->get($uid, $field) ?: '');

        return new self($json);
    }

    public function add(int $fd): self
    {
        $this->data[] = $fd;

        return $this;
    }

    public function del(int $fd): self
    {
        $array = array_fill_keys($this->data, 1);

        if (isset($array[$fd])) {
            unset($array[$fd]);
        }

        $this->data = array_keys($array);

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function count()
    {
        return count($this->data);
    }
}
