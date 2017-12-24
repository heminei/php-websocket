# Simple and Powerfull PHP WebSocket Server and Client
Simple and powerful PHP WebSocket implementation for PHP

## Supports
- multiple paths
- check-origin
- limit clients
- sends status codes on close connections

## Requirements
PHP 7.1

## Installation

Enter in composer.json
```json
{
    "require": {
        "hemiframe/php-websocket": ">=7.1.0"
    }
}
```

or run in console: `php composer.phar require hemiframe/php-websocket`


## Server example

```php
require_once('vendor/autoload.php');

$socket = new \HemiFrame\Lib\WebSocket\WebSocket("localhost", 8080);
$socket->on("receive", function($client, $data) use($socket) {
	foreach ($socket->getClients() as $item) {
		if ($item->id != $client->id) {
			$socket->sendData($item, $data);
		}
	}
});
$socket->startServer();
```

## Client example

```php
require_once('vendor/autoload.php');

$socket = new \HemiFrame\Lib\WebSocket\WebSocket('localhost', 8080);
$socket->on("receive", function($client, $data) use($socket) {
});
$client = $socket->connect();
if ($client) {
	$socket->sendData($client, "Message");
	$socket->disconnectClient($client);
}
```

## Documentation

### Events:

```php
$socket->on("connect", function(\HemiFrame\Lib\WebSocket\Client $client) {
});

$socket->on("receive", function(\HemiFrame\Lib\WebSocket\Client $client, $data) {
});

$socket->on("send", function(\HemiFrame\Lib\WebSocket\Client $client, $data) {
});

$socket->on("ping", function(\HemiFrame\Lib\WebSocket\Client $client, $data) {
});

$socket->on("pong", function(\HemiFrame\Lib\WebSocket\Client $client, $data) {
});

$socket->on("disconnect", function(\HemiFrame\Lib\WebSocket\Client $client, $statusCode, $reason) {
});

$socket->on("error", function($socket, $client, $phpError, $errorMessage, $errorCode) {
});
```

### Methods
```php
$socket->getSocket();

$socket->getClients();

$socket->getClientsByPath($path = "/");

$socket->getAllowedOrigins();

$socket->setAllowedOrigins($allowedOrigins);

$socket->getMaxClients();

$socket->setMaxClients($maxClients);

$socket->getBufferSize();

$socket->setBufferSize($bufferSize);

$socket->getUserAgent();

$socket->setUserAgent($userAgent);

$socket->setEnableLogging($enableLogging);

$socket->create();

$socket->close();

$socket->setOption($socket, $level, $optname, $optval);

$socket->bind($address, $port = 0);

$socket->listen($backlog = 0);

$socket->select(&$read, &$write, &$except, $backlog, $tv_usec = 0);

$socket->accept();

$socket->write($socket, $message = null);

$socket->recv($socket);

$socket->read($socket);

$socket->getLastErrorCode();

$socket->getLastErrorMessage();

$socket->connect($path = "/", $origin = null);

$socket->startServer();

/**
* Disconnect all clients and close main socket
*/
$socket->stopServer();

$socket->loop();

$socket->trigger($name, $arguments);

$socket->on($name, $function);

$socket->one($name, $function);

$socket->sendData($client, $data);

$socket->disconnectClient($client, $statusCode = self::STATUS_CLOSE_NORMAL, $reason = null);
```