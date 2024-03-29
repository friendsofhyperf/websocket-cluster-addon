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

use Hyperf\Redis\Redis;

class RedisSetStatus implements StatusInterface
{
    public function __construct(private Redis $redis, private string $key) {}

    public function set(int|string $uid, bool $status = true): void
    {
        if (! $status) {
            $this->redis->sRem($this->key, $uid);
        } else {
            $this->redis->sAdd($this->key, $uid);
        }
    }

    public function get(int|string $uid): bool
    {
        return $this->redis->sIsMember($this->key, $uid);
    }

    public function multiSet(array $uids, bool $status): void
    {
        if (! $status) {
            $this->redis->sRem($this->key, ...$uids);
        } else {
            $this->redis->sAdd($this->key, ...$uids);
        }
    }

    public function multiGet(array $uids): array
    {
        $uids = array_filter($uids);
        $result = array_fill_keys($uids, false);
        $tmpKey = uniqid($this->key . ':');

        try {
            $this->redis->sAdd($tmpKey, ...$uids);
            $onlines = $this->redis->sInter($tmpKey, $this->key);
            $onlines = array_fill_keys($onlines, true);
            $result = array_replace($result, $onlines);
        } finally {
            $this->redis->del($tmpKey);
        }

        return $result;
    }

    public function count(): int
    {
        return $this->redis->sCard($this->key);
    }
}
