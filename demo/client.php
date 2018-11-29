<?php

require_once __DIR__ . '/../vendor/autoload.php';


$socket = new \HemiFrame\Lib\WebSocket\WebSocket("localhost", 8080);
$socket->setEnableLogging(true);

$client = $socket->connect();
if ($client !== false) {
	$socket->sendData($client, "1");
	sleep(1);
	$socket->sendData($client, "2");
	sleep(1);
	$socket->sendData($client, "3");
	sleep(1);
	$socket->disconnectClient($client);
}
