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
namespace FriendsOfHyperf\WebsocketClusterAddon\Util;

use Hyperf\Redis\Redis;
use RedisCluster;

class Bitmap
{
    public const BIT_SIZE = 'u1';

    private bool $isCluster = false;

    public function __construct(private Redis $redis)
    {
        $this->isCluster = value(function () use ($redis) {
            $redisConnection = (fn () => $this->getConnection(true))->call($redis);
            $connection = (fn () => $this->connection)->call($redisConnection);

            return $connection instanceof RedisCluster;
        });
    }

    public function multiSet(string $key, array $items, string $size = self::BIT_SIZE)
    {
        $params = [];

        if ($this->isCluster) {
            $params[] = $key;
        }

        $params[] = 'BITFIELD';
        $params[] = $key;

        foreach ($items as $k => $v) {
            $params[] = 'set';
            $params[] = $size;
            $params[] = $k;
            $params[] = $v;
        }

        return $this->redis->rawCommand(...$params);
    }

    public function multiGet(string $key, array $items, string $size = self::BIT_SIZE)
    {
        $params = [];

        if ($this->isCluster) {
            $params[] = $key;
        }

        $params[] = 'BITFIELD';
        $params[] = $key;

        foreach ($items as $k) {
            $params[] = 'get';
            $params[] = $size;
            $params[] = $k;
        }

        $result = $this->redis->rawCommand(...$params);

        if (is_array($result)) {
            $ret = [];

            foreach ($result as $k => $v) {
                $ret[$items[$k]] = (int) $v;
            }

            return $ret;
        }

        return [];
    }

    public function count(string $key)
    {
        $params = [];

        if ($this->isCluster) {
            $params[] = $key;
        }

        $params[] = 'BITCOUNT';
        $params[] = $key;

        return $this->redis->rawCommand(...$params);
    }
}
