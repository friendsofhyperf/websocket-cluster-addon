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

use FriendsOfHyperf\WebsocketClusterAddon\Util\Bitmap;
use Hyperf\Redis\Redis;

class RedisBitmapStatus implements StatusInterface
{
    private Bitmap $bitmap;

    private string $key;

    public function __construct(Redis $redis, string $key)
    {
        $this->key = $key;
        $this->bitmap = new Bitmap($redis);
    }

    public function set($uid, bool $status = true): void
    {
        $this->bitmap->multiSet($this->key, [(int) $uid => $status ? 1 : 0]);
    }

    public function get($uid): bool
    {
        $status = $this->multiGet([$uid]);

        return $status[$uid] ?? false;
    }

    public function multiGet(array $uids): array
    {
        $uids = array_map(fn ($uid) => (int) $uid, $uids);
        $result = $this->bitmap->multiGet($this->key, $uids);

        return array_map(fn ($status) => $status === 1, $result);
    }

    public function count(): int
    {
        return $this->bitmap->count($this->key);
    }
}
