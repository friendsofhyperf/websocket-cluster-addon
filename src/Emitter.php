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
namespace FriendsOfHyperf\WebsocketClusterAddon;

use Psr\Container\ContainerInterface;

class Emitter
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array|object|string $data
     */
    public function emit(int $uid, $data, bool $isLocal = false): void
    {
        $data = $this->formatData($data);
        /** @var Addon $addon */
        $addon = $this->container->get(Addon::class);
        $addon->broadcast(serialize([$uid, $data, $isLocal]));
    }

    /**
     * @param array|object|string $data
     */
    public function broadcast($data, bool $isLocal = false): void
    {
        $this->emit(0, $data, $isLocal);
    }

    /**
     * @param array|object|string $data
     */
    protected function formatData($data): string
    {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        } elseif (is_callable([$data, '__toString'])) {
            $data = $data->__toString();
        }

        return $data;
    }
}
