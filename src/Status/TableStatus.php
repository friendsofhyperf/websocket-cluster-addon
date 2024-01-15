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

namespace FriendsOfHyperf\WebsocketClusterAddon\Status;

use Swoole\Table;

use function Hyperf\Tappable\tap;

class TableStatus implements StatusInterface
{
    private Table $table;

    public function initTable(int $size = 10240): void
    {
        $this->table = tap(new Table($size), function (Table $table) {
            $table->column('status', Table::TYPE_INT, 1);
            $table->create();
        });
    }

    public function set($uid, bool $status = true): void
    {
        $this->table->set($uid, ['status' => $status ? 1 : 0]);
    }

    public function get($uid): bool
    {
        return (bool) $this->table->get($uid)['status'] ?? false;
    }

    public function multiSet(array $uids, bool $status): void
    {
        foreach ($uids as $uid) {
            $this->set($uid, $status);
        }
    }

    public function multiGet(array $uids): array
    {
        $result = [];

        foreach ($uids as $uid) {
            $result[$uid] = $this->get($uid);
        }

        return $result;
    }

    public function count(): int
    {
        return $this->table->count();
    }
}
