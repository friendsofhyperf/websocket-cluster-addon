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

use FriendsOfHyperf\WebsocketClusterAddon\Status\StatusInterface;
use Swoole\Table;

use function Hyperf\Tappable\tap;

class TableClient implements ClientInterface
{
    public const FD = 'fd';

    public const FDS = 'fds';

    protected Table $users;

    protected Table $connections;

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

    public function add(int $fd, int|string $uid): void
    {
        $fds = $this->clients($uid);
        $fds[] = $fd;

        $this->users->set((string) $uid, [self::FDS => json_encode($fds)]);
        $this->connections->set((string) $fd, [self::FD => $fd]);
    }

    public function renew(int $fd, int|string $uid): void {}

    public function del(int $fd, int|string $uid): void
    {
        $fds = $this->clients($uid);
        $index = array_search($fd, $fds);
        if ($index !== false) {
            unset($fds[$index]);
        }

        $this->users->set((string) $uid, [self::FDS => json_encode($fds)]);
        $this->connections->del((string) $fd);
    }

    public function clients(int|string $uid): array
    {
        $fds = $this->users->get((string) $uid, self::FDS);

        if ($fds) {
            return json_decode($fds, true);
        }

        return [];
    }

    public function size(null|int|string $uid = null): int
    {
        if (! $uid) {
            return $this->connections->count();
        }

        return count($this->clients($uid));
    }

    public function clearUpExpired(): void {}

    public function getOnlineStatus(int|string $uid): bool
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

    public function getStatusInstance(): ?StatusInterface
    {
        return null;
    }
}
