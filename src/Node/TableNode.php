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

use function Hyperf\Tappable\tap;

class TableNode implements NodeInterface
{
    public const FD = 'fd';

    public const FDS = 'fds';

    private Table $users;

    private Table $connections;

    public function initTable(int $size = 10240): void
    {
        $this->users = tap(new Table($size), function (Table $table) {
            $table->column(self::FDS, Table::TYPE_STRING, 102400);
            $table->create();
        });

        $this->connections = tap(new Table($size * 20), function (Table $table) {
            $table->column(self::FD, Table::TYPE_INT);
            $table->create();
        });
    }

    /**
     * @param int|string $uid
     */
    public function add(int $fd, $uid): void
    {
        $fds = $this->clients($uid);
        $fds[] = $fd;

        $this->users->set((string) $uid, [self::FDS => json_encode($fds)]);
        $this->connections->set((string) $fd, [self::FD => $fd]);
    }

    /**
     * @param int|string $uid
     */
    public function del(int $fd, $uid): void
    {
        $fds = $this->clients($uid);
        $index = array_search($fd, $fds);

        if ($index !== false) {
            unset($fds[$index]);
        }

        $this->users->set((string) $uid, [self::FDS => json_encode($fds)]);
        $this->connections->del((string) $fd);
    }

    public function users(): int
    {
        return $this->users->count();
    }

    public function size($uid = null): int
    {
        if (! $uid) {
            return $this->connections->count();
        }

        return count($this->clients($uid));
    }

    public function clients($uid = null): array
    {
        if (! $uid) {
            $fds = [];

            foreach ($this->connections as $row) {
                $fds[] = $row[self::FD];
            }

            return $fds;
        }

        $fds = $this->users->get((string) $uid, self::FDS);

        if (! $fds) {
            return [];
        }

        return json_decode($fds, true);
    }

    public function flush(?string $serverId = null): void
    {
    }
}
