# websocket-cluster-addon

[![Latest Test](https://github.com/friendsofhyperf/websocket-cluster-addon/workflows/tests/badge.svg)](https://github.com/friendsofhyperf/websocket-cluster-addon/actions)
[![Latest Stable Version](https://poser.pugx.org/friendsofhyperf/websocket-cluster-addon/version.png)](https://packagist.org/packages/friendsofhyperf/websocket-cluster-addon)
[![Total Downloads](https://poser.pugx.org/friendsofhyperf/websocket-cluster-addon/d/total.png)](https://packagist.org/packages/friendsofhyperf/websocket-cluster-addon)
[![GitHub license](https://img.shields.io/github/license/friendsofhyperf/websocket-cluster-addon)](https://github.com/friendsofhyperf/websocket-cluster-addon)

Websocket cluster addon base redis subscribe.

## Installation

- Requirements

  - PHP >= 7.4
  - Swoole >= 4.5.10
  - hyperf/websocket-server >= 2.2.0

- Composer install

~~~base
composer require friendsofhyperf/websocket-cluster-addon:^3.1
~~~

- Publish

~~~bash
php bin/hyperf.php vendor:publish friendsofhyperf/websocket-cluster-addon
~~~

## Usage

- Send message

~~~php
use FriendsOfHyperf\WebsocketClusterAddon\Emitter;
$emitter = $container->get(Emitter::class);
$emitter->emit($contactId, $message);
~~~

- Broadcast message

~~~php
use FriendsOfHyperf\WebsocketClusterAddon\Emitter;
$emitter = $container->get(Emitter::class);
$emitter->broadcast($message);
~~~

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
