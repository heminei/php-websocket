# Simple and Powerfull PHP WebSocket Server and Client
Simple and powerful PHP WebSocket implementation for PHP

## Supports
- multiple paths
- check-origin
- limit clients
- sends status codes on close connections

## Requirements
PHP >= 7.0

## Installation

Enter in composer.json
```json
{
    "require": {
        "hemiframe/php-websocket": "~1.5"
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
        /* @var $item \HemiFrame\Lib\WebSocket\Client */
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

/**
* Get connected clients
* @return array
*/
$socket->getClients();

/**
* Get connected clients, filtered by path
* @return array
*/
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

/**
* Get last socket error code
* Alias of socket_last_error
* @return int
*/
$socket->getLastErrorCode();

/**
* Get last socket error message
* @return string
*/
$socket->getLastErrorMessage();

/**
* Connect to websocket server. Send and validate handshake request.
* @param string $path Send custom path to server
* @param string $origin Send custom origin host to server
* @return mixed Return client object when is successfully connected to server or FALSE when is not.
*/
$socket->connect($path = "/", $origin = null);

/**
* Run WebSocket server.
* Create main socket.
* Bind address and port.
* Listens for a connection on a socket.
* Call main processing loop
*/
$socket->startServer();

/**
* Disconnect all clients and close main socket
*/
$socket->stopServer();

/**
* Main processing loop. Check for new clients, incomming data, diconnected clients
*/
$socket->loop();

/**
* Trigger event
* @param string $name Event name
* @param array $arguments Arguments for sending
*/
$socket->trigger($name, $arguments);

/**
* Subscribe for event.
* @param string $name Event name
* @param function $function Callback function
*/
$socket->on($name, $function);

/**
* Subscribe on event. Call only first time
* @param string $name Event name
* @param function $function Callback function
*/
$socket->one($name, $function);

/**
* Send data to client
* @param object $client
* @param string $data
*/
$socket->sendData($client, $data);

/**
* Disconnect client. You can send custom status code and reason
* @param object $client
* @param int $statusCode
* @param string $reason
* @return self
*/
$socket->disconnectClient($client, $statusCode = self::STATUS_CLOSE_NORMAL, $reason = null);
```
