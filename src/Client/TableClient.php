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
namespace FriendsOfHyperf\WebsocketClusterAddon\Client;

use FriendsOfHyperf\WebsocketClusterAddon\Adapter\TableAdapter;
use Swoole\Table;

class TableClient implements ClientInterface
{
    /**
     * @var Table
     */
    private $userTable;

    /**
     * @var Table
     */
    private $connTable;

    public function add(int $fd, $uid): void
    {
        $fds = $this->makeAdapter($uid)->add($fd)->__toString();

        $this->userTable->set((string) $uid, ['fds' => $fds]);
        $this->connTable->set((string) $fd, ['fd' => $fd]);
    }

    public function renew(int $fd, $uid): void
    {
    }

    public function del(int $fd, $uid): void
    {
        $fds = $this->makeAdapter($uid)->del($fd)->__toString();

        $this->userTable->set((string) $uid, ['fds' => $fds]);
        $this->connTable->del((string) $fd);
    }

    public function clients($uid): array
    {
        return $this->makeAdapter($uid)->toArray();
    }

    public function size($uid): int
    {
        if ($uid == 0) {
            return $this->connTable->count();
        }

        return $this->makeAdapter($uid)->count();
    }

    public function clearUpExpired(): void
    {
    }

    public function getOnlineStatus($uid): bool
    {
        return $this->size($uid) > 0;
    }

    public function multiGetOnlineStatus(array $uids): array
    {
        $result = [];

        foreach ($uids as $uid) {
            $result[$uid] = $this->size($uid) > 0;
        }

        return $result;
    }

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
    protected function makeAdapter($uid): TableAdapter
    {
        $serialized = (string) ($this->userTable->get((string) $uid, 'fds') ?: '');

        return new TableAdapter($serialized);
    }
}
