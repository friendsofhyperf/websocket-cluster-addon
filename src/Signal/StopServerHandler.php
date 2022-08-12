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
namespace FriendsOfHyperf\WebsocketClusterAddon\Signal;

use FriendsOfHyperf\WebsocketClusterAddon\Server;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;

/**
 * @Signal(priority=-1)
 */
class StopServerHandler implements SignalHandlerInterface
{
    public function __construct(protected ConfigInterface $config, protected Server $server, protected StdoutLoggerInterface $logger)
    {
    }

    public function listen(): array
    {
        return [
            [self::WORKER, SIGTERM],
            [self::WORKER, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        if ($signal !== SIGINT) {
            $time = $this->config->get('server.settings.max_wait_time', 5);
            sleep($time);
        }

        $this->server->stop();

        $this->logger->info(sprintf('[WebsocketClusterAddon] @%s #%s stopped by %s.', $this->server->getServerId(), $this->server->getWorkerId(), self::class));
    }
}
