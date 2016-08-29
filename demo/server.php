<?php

require_once '../src/HemiFrame/Lib/WebSocket.php';

set_time_limit(0);

$socket = new \HemiFrame\Lib\WebSocket("localhost", 8080);
$socket->setEnableLogging(true);

$socket->on("receive", function($client, $data) use($socket) {
	foreach ($socket->getClientsByPath($client->path) as $item) {
//		if ($item->id != $client->id) {
		$socket->sendData($item, $data);
//		}
	}
});

$socket->on("error", function($socket, $client, $phpError, $errorMessage, $errorCode) {
	var_dump("Error: => " . implode(" => ", $phpError));
});

$socket->startServer();
