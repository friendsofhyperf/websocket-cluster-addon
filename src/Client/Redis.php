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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Pool\PoolFactory;

class Redis extends \Hyperf\Redis\Redis
{
    public function __construct(protected PoolFactory $factory, ConfigInterface $config)
    {
        $this->poolName = $config->get('websocket_cluster.client.pool', 'default');
        parent::__construct($factory);
    }
}
