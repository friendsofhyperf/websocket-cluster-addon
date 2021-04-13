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
namespace FriendsOfHyperf\WebsocketClusterAddon\Connection;

use ArrayAccess;
use Countable;
use Hyperf\Utils\Contracts\Arrayable;
use Swoole\Table;

class TableConnector implements ArrayAccess, Countable, Arrayable
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

    public static function make(Table $table, string $key, string $field = 'fds'): self
    {
        $json = (string) ($table->get($key, $field) ?: '');

        return new self($json);
    }

    public function add(int $fd): self
    {
        $this->data[$fd] = 1;

        return $this;
    }

    public function del(int $fd): self
    {
        unset($this->data[$fd]);

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists($offset)
    {
        return isset($this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if (isset($this->data[$offset])) {
            unset($this->data[$offset]);
        }
    }

    public function count()
    {
        return count($this->data);
    }
}
