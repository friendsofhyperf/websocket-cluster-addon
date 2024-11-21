# websocket-cluster-addon

[![Latest Test](https://github.com/friendsofhyperf/websocket-cluster-addon/workflows/tests/badge.svg)](https://github.com/friendsofhyperf/websocket-cluster-addon)
[![Latest Stable Version](https://img.shields.io/packagist/v/friendsofhyperf/websocket-cluster-addon)](https://packagist.org/packages/friendsofhyperf/websocket-cluster-addon)
[![Total Downloads](https://img.shields.io/packagist/dt/friendsofhyperf/websocket-cluster-addon)](https://packagist.org/packages/friendsofhyperf/websocket-cluster-addon)
[![License](https://img.shields.io/packagist/l/friendsofhyperf/websocket-cluster-addon)](https://github.com/friendsofhyperf/websocket-cluster-addon)
[![GitHub license](https://img.shields.io/github/license/friendsofhyperf/websocket-cluster-addon)](https://github.com/friendsofhyperf/websocket-cluster-addon)

Websocket cluster addon base redis subscribe.

## Installation

- Requirements

  - PHP >= 8.1
  - Swoole >= 5.0.0
  - hyperf/websocket-server >= 3.1.0

- Composer install

```base
composer require friendsofhyperf/websocket-cluster-addon:^5.0
```

- Publish

```bash
php bin/hyperf.php vendor:publish friendsofhyperf/websocket-cluster-addon
```

## Usage

- Send message

```php
use FriendsOfHyperf\WebsocketClusterAddon\Emitter;
$emitter = $container->get(Emitter::class);
$emitter->emit($contactId, $message);
```

- Broadcast message

```php
use FriendsOfHyperf\WebsocketClusterAddon\Emitter;
$emitter = $container->get(Emitter::class);
$emitter->broadcast($message);
```

## Examples

- [Controller](examples/Controller/WebSocketController.php)
- [Listener](examples/Listener/StatusChangedListener.php)

## Drivers

- Node
  - [x] Memory `default`
  - [x] Redis
  - [x] Swoole Table

- Client
  - [x] Redis `default`
  - [x] Swoole Table
