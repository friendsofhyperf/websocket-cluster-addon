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

use Swoole\Table;

class TableNode implements NodeInterface
{
    /**
     * @var Table
     */
    private $userTable;

    /**
     * @var Table
     */
    private $connTable;

    public function initTable(int $size = 10240): void
    {
        $this->userTable = tap(new Table($size), function (Table $table) {
            $table->column('fds', Table::TYPE_STRING, 102400);
            $table->create();
        });

        $this->connTable = tap(new Table($size * 20), function (Table $table) {
            $table->column('fd', Table::TYPE_INT);
            $table->create();
        });
    }

    /**
     * @param int|string $uid
     */
    public function add(int $fd, $uid): void
    {
        $fds = $this->makeAdapter($uid)->add($fd)->__toString();

        $this->userTable->set((string) $uid, ['fds' => $fds]);
        $this->connTable->set((string) $fd, ['fd' => $fd]);
    }

    /**
     * @param int|string $uid
     */
    public function del(int $fd, $uid): void
    {
        $fds = $this->makeAdapter($uid)->del($fd)->__toString();

        $this->userTable->set((string) $uid, ['fds' => $fds]);
        $this->connTable->del((string) $fd);
    }

    /**
     * @param int|string $uid
     */
    public function size($uid): int
    {
        if ($uid == 0) {
            return $this->connTable->count();
        }

        return $this->makeAdapter($uid)->count();
    }

    public function users(): int
    {
        return $this->userTable->count();
    }

    /**
     * @param int|string $uid
     */
    public function clients($uid): array
    {
        if ($uid == 0) {
            $fds = [];

            foreach ($this->connTable as $row) {
                $fds[] = $row['fd'];
            }

            return $fds;
        }

        return $this->makeAdapter($uid)->toArray();
    }

    public function flush(?string $serverId = null): void
    {
    }

    /**
     * @param int|string $uid
     */
    protected function makeAdapter($uid): TableAdapter
    {
        $value = (string) ($this->userTable->get((string) $uid, 'fds') ?: '');
        $data = unserialize($value);
        $data = is_array($data) ? $data : [];

        return new TableAdapter($data);
    }
}
