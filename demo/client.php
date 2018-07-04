<?php

require_once '../src/HemiFrame/Lib/WebSocket.php';


$socket = new \HemiFrame\Lib\WebSocket("localhost", 8080);
$client = $socket->connect();

if ($client !== false) {
	$socket->sendData($client, "My data");
	$socket->disconnectClient($client);
}
