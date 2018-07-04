<?php

namespace HemiFrame\Lib\WebSocket;

/**
 * @author Heminei
 * @link https://github.com/heminei/php-websocket
 * @version 1.5.1
 */
class WebSocket
{

    const STATUS_CLOSE_NORMAL = 1000;
    const STATUS_CLOSE_GOING_AWAY = 1001;
    const STATUS_CLOSE_PROTOCOL_ERROR = 1002;
    const STATUS_CLOSE_UNSUPPORTED = 1003;
    const STATUS_CLOSE_NO_STATUS = 1005;
    const STATUS_CLOSE_ABNORMAL = 1006;

    private $type = "server";
    private $socket = null;
    private $address = null;
    private $port = null;
    private $clients = [];
    private $events = [];
    private $allowedOrigins = [];
    private $maxClients = 1000;
    private $bufferSize = 2048;
    private $userAgent = "php-client";
    private $enableLogging = false;

    public function __construct(string $address = null, int $port = 8080)
    {
        if (!function_exists("socket_create")) {
            throw new \Exception("Function socket_create not found");
        }

        $this->address = $address;
        $this->port = $port;
    }

    public function __destruct()
    {
        $this->close($this->socket);
    }

    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Get connected clients
     * @return array
     */
    public function getClients() : array
    {
        return $this->clients;
    }

    /**
     *
     * @return array
     */
    public function getClientsByPath(string $path = "/") : array
    {
        $clients = array_filter($this->getClients(), function (Client $client) use ($path) {
            if ($client->getPath() == $path) {
                return true;
            }
            return false;
        });
        return $clients;
    }

    /**
     *
     * @return array
     */
    public function getAllowedOrigins() : array
    {
        return $this->allowedOrigins;
    }

    /**
     *
     * @param array $allowedOrigins
     * @return self
     */
    public function setAllowedOrigins(array $allowedOrigins) : self
    {
        $this->allowedOrigins = $allowedOrigins;

        return $this;
    }

    /**
     * Get max clients limit
     * @return int
     */
    public function getMaxClients() : int
    {
        return $this->maxClients;
    }

    /**
     * Set max clients limit
     * @param int $maxClients
     * @return self
     */
    public function setMaxClients(int $maxClients) : self
    {
        $this->maxClients = $maxClients;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function getBufferSize() : int
    {
        return $this->bufferSize;
    }

    /**
     *
     * @param int $bufferSize
     * @return self
     */
    public function setBufferSize(int $bufferSize) : self
    {
        $this->bufferSize = $bufferSize;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getUserAgent() : string
    {
        return $this->userAgent;
    }

    /**
     *
     * @param string $userAgent
     * @return self
     */
    public function setUserAgent(string $userAgent) : self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     *
     * @param boolean $enableLogging
     * @return self
     */
    function setEnableLogging(bool $enableLogging) : self
    {
        $this->enableLogging = $enableLogging;
        return $this;
    }

    /**
     * socket_create
     * @return self
     */
    public function create()
    {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            $this->onError($this->socket);
        }

        return $this;
    }

    /**
     *
     * @param resource $socket
     * @return self
     */
    public function close($socket) : self
    {
        if (is_resource($socket)) {
            socket_close($socket);
        }
        return $this;
    }

    /**
     * socket_set_option
     * @param resource $socket
     * @param int $level
     * @param int $optname
     * @param mixed $optval
     * @return bool
     */
    public function setOption($socket, int $level, int $optname, $optval) : bool
    {
        $result = @socket_set_option($socket, $level, $optname, $optval);
        if ($result === false) {
            $this->onError($socket);
        }
        return $result;
    }

    /**
     * socket_bind
     * @param string $address
     * @param int $port
     * @return bool
     */
    public function bind(string $address, int $port = 0) : bool
    {
        $result = @socket_bind($this->socket, $address, $port);
        if ($result === false) {
            $this->onError($this->socket);
        }
        return $result;
    }

    /**
     * socket_listen
     * @param int $backlog
     * @return bool
     */
    public function listen(int $backlog = 0) : bool
    {
        $result = @socket_listen($this->socket, $backlog);
        if ($result === false) {
            $this->onError($this->socket);
        }
        return $result;
    }

    /**
     * socket_select
     * @param array &$read
     * @param array &$write
     * @param array &$except
     * @param int $backlog
     * @param int $tv_usec
     * @return int
     */
    public function select(array &$read, array &$write, array &$except, int $backlog, int $tv_usec = 0)
    {
        $result = @socket_select($read, $write, $except, $backlog, $tv_usec);
        if ($result === false) {
            $this->onError($this->socket);
        }
        return $result;
    }

    /**
     * socket_accept
     * @return resource
     */
    public function accept()
    {
        $resource = @socket_accept($this->socket);
        if ($resource === false) {
            $this->onError($this->socket);
        }
        return $resource;
    }

    /**
     * socket_write
     * @param resource $socket
     * @param string $message
     * @return int
     */
    public function write($socket, $message = null)
    {
        $bytes = @socket_write($socket, $message, strlen($message));
        if ($bytes === false) {
            $this->onError($this->socket);
        }

        return $bytes;
    }

    /**
     * socket_recv
     * @param resource $socket
     * @return string
     */
    public function recv($socket)
    {
        $buf = null;
        while ($receivedBytes = @socket_recv($socket, $r_data, $this->bufferSize, MSG_DONTWAIT)) {
            $buf .= $r_data;
        }

        return $buf;
    }

    /**
     * socket_read
     * @param resource $socket
     * @return string
     */
    public function read($socket)
    {
        $buf = "";
        while (true) {
            $out = @socket_read($socket, $this->bufferSize);
            if ($out === "" || $out === false) {
                break;
            }
            $buf .= $out;
            socket_set_nonblock($socket);
        }
        socket_set_block($socket);

        return $buf;
    }

    /**
     * socket_last_error
     * @return int
     */
    public function getLastErrorCode() : int
    {
        return socket_last_error();
    }

    /**
     * socket_strerror
     * @return string
     */
    public function getLastErrorMessage()
    {
        $errorCode = $this->getLastErrorCode();
        return socket_strerror($errorCode);
    }

    /**
     *
     * @param string $path
     * @param string $origin
     * @return Client|bool
     */
    public function connect(string $path = "/", string $origin = null)
    {
        $this->type = "client";
        $this->create();
        $result = @socket_connect($this->socket, $this->address, $this->port);
        if ($result === false) {
            $this->onError($this->socket);
            return false;
        }

        $client = $this->createClient($this->getSocket());

        $key = $this->generateWebSocketKey();
        $header = "GET $path HTTP/1.1\r\n";
        $header .= "Host: " . $this->address . ":" . $this->port . "\r\n";
        $header .= "User-Agent: " . $this->userAgent . "\r\n";
        $header .= "Upgrade: websocket\r\n";
        $header .= "Connection: Upgrade\r\n";
        $header .= "Sec-WebSocket-Key: " . $key . "\r\n";
        if (!empty($origin)) {
            $header .= "Origin: " . $origin . "\r\n";
        }
        $header .= "Sec-WebSocket-Version: 13\r\n\r\n";
        $this->write($client->getSocket(), $header);

        $buf = $this->read($client->getSocket());

        if ($buf === false) {
            $this->disconnectClient($client, self::STATUS_CLOSE_PROTOCOL_ERROR);
            return false;
        }

        $client->setHeaders($this->parseHeaders($buf));

        if (isset($client->getHeaders()['Sec-WebSocket-Accept'])) {
            $secWebSocketAccept = $client->getHeaders()['Sec-WebSocket-Accept'];
            $expectedResonse = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

            if ($secWebSocketAccept === $expectedResonse) {
                $client->setHandshake(true);
            }
        }

        if ($client->getHandshake()) {
            $this->clients[] = $client;
        } else {
            $this->disconnectClient($client, self::STATUS_CLOSE_PROTOCOL_ERROR);
        }

        return $client;
    }

    /**
     * Run WebSocket server
     * @return self
     */
    public function startServer() : self
    {
        $this->create();
        $this->setOption($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        $this->bind($this->address, $this->port);
        $this->listen();
        $this->loop();

        return $this;
    }

    /**
     * Disconnect all clients and close main socket
     * @return self
     */
    public function stopServer() : self
    {
        foreach ($this->clients as $client) {
            $this->disconnectClient($client, self::STATUS_CLOSE_GOING_AWAY);
        }
        $this->close($this->socket);

        return $this;
    }

    public function loop()
    {
        while (is_resource($this->socket)) {
            if ($this->type == "server") {
                $read = [$this->socket];
            }
            foreach ($this->clients as $client) {
                $read[] = $client->getSocket();
            }
            $write = [];
            $except = [];
            if ($this->select($read, $write, $except, 0, 10) === false) {
                $this->onError($this->socket);
            }

            if (in_array($this->socket, $read) && $this->type == "server") {
                $client = $this->createClient($this->accept());

                if (count($this->clients) >= $this->maxClients) {
                    $this->log("Max clients limit reached", $client);
                    $this->log("Client is disconnected", $client);
                    $this->disconnectClient($client, self::STATUS_CLOSE_PROTOCOL_ERROR, "Max clients limit reached");
                }

                $buf = $this->read($client->getSocket());
                if ($buf === false) {
                    $this->onError($this->socket);
                }
                if (!$client->getHandshake()) {
                    if ($this->processClientHandshake($client, $buf)) {
                        if ($this->checkOrigin($client->getHeaders())) {
                            $this->clients[] = $client;
                            $this->log("connect", $client);
                            $this->trigger("connect", [
                                $client
                            ]);
                        } else {
                            $this->log("Invalid origin", $client);
                            $this->log("Client is disconnected", $client);
                            $this->disconnectClient($client, self::STATUS_CLOSE_PROTOCOL_ERROR, "Invalid origin");
                        }
                    } else {
                        $this->log("Failed process handchake", $client);
                        $this->disconnectClient($client, self::STATUS_CLOSE_PROTOCOL_ERROR, "Failed process handchake");
                    }
                }

                $masterSocketIndex = array_search($this->socket, $read);
                unset($read[$masterSocketIndex]);
            }

            foreach ($read as $changedSocket) {
                $buf = $this->read($changedSocket);
                $client = $this->getClientBySocket($changedSocket);

                if (empty($buf)) {
                    $this->log("Can't read data", $client);
                    $this->disconnectClient($client, self::STATUS_CLOSE_PROTOCOL_ERROR);
                    continue;
                }

                $data = $this->hybi10Decode($client, $buf);

                if ($data['payload'] === false) {
                    $this->log("Can't decode data", $client);
                    $this->disconnectClient($client, self::STATUS_CLOSE_PROTOCOL_ERROR);
                    continue;
                }

                switch ($data['type']) {
                    case 'text':
                        $this->log("Receive data: " . $data['payload'], $client);
                        $this->trigger("receive", [
                            $client,
                            $data['payload'],
                        ]);
                        break;

                    case 'ping':
                        $this->log("ping", $client);
                        $this->write($changedSocket, $this->hybi10Encode($client, "", "pong"));
                        $this->trigger("ping", [
                            $client,
                            $data['payload'],
                        ]);
                        break;

                    case 'pong':
                        $this->log("pong", $client);
                        $this->trigger("pong", [
                            $client,
                            $data['payload'],
                        ]);
                        break;

                    case 'close':
                        if (strlen($data['payload']) >= 2) {
                            $statusCode = unpack("n", substr($data['payload'], 0, 2));
                            $reason = substr($data['payload'], 2);
                            $this->disconnectClient($client, $statusCode[1], $reason);
                        } else {
                            $this->disconnectClient($client);
                        }
                        break;
                }
            }

//			var_dump(count($this->clients));
//			var_dump("loop");
//			usleep(50000);
//			sleep(1);
        }
    }

    /**
     * Trigger event
     * @param string $name
     * @param array $arguments
     */
    public function trigger(string $name, array $arguments = []) : self
    {
        foreach ($this->events as $key => $event) {
            if ($event['name'] == $name && !empty($event['function'])) {
                call_user_func_array($event['function'], $arguments);
                if ($event['type'] == "one") {
                    unset($this->events[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Subscribe on event
     * @param string $name
     * @param \Closure $function
     */
    public function on(string $name, \Closure $function)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException("Invalid event name");
        }
        if (empty($function)) {
            throw new \InvalidArgumentException("Invalid closure");
        }
        $this->events[] = [
            "name" => $name,
            "function" => $function,
            "type" => "on",
        ];
    }

    /**
     * Subscribe on event. Call only first time
     * @param string $name
     * @param function $function
     */
    public function one(string $name, \Closure $function)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException("Invalid event name");
        }
        if (empty($function)) {
            throw new \InvalidArgumentException("Invalid closure");
        }
        $this->events[] = [
            "name" => $name,
            "function" => $function,
            "type" => "one",
        ];
    }

    /**
     * Send data to client
     * @param Client $client
     * @param string $data
     */
    public function sendData(Client $client, $data)
    {
        if ($this->checkClientExistBySocket($client->getSocket())) {
            $response = $this->hybi10Encode($client, $data, "text");
            $this->write($client->getSocket(), $response);
            $this->trigger("send", [
                $client,
                $data
            ]);
        }
    }

    /**
     * Disconnect client
     * @param Client $client
     * @return self
     */
    public function disconnectClient(Client $client, $statusCode = self::STATUS_CLOSE_NORMAL, $reason = null) : self
    {
        $payload = pack('n', $statusCode);

        if (empty($reason)) {
            switch ($statusCode) {
                case self::STATUS_CLOSE_NORMAL:
                    $reason = 'normal closure';
                    break;

                case self::STATUS_CLOSE_GOING_AWAY:
                    $reason = 'going away';
                    break;

                case self::STATUS_CLOSE_PROTOCOL_ERROR:
                    $reason = 'protocol error';
                    break;

                case self::STATUS_CLOSE_UNSUPPORTED:
                    $reason = 'unknown data (opcode)';
                    break;

                case 1004:
                    $reason = 'frame too large';
                    break;

                case 1007:
                    $reason = 'utf8 expected';
                    break;

                case 1008:
                    $reason = 'message violates server policy';
                    break;
            }
        }

        $this->write($client->getSocket(), $this->hybi10Encode($client, $payload . $reason, "close", true));
        $this->close($client->getSocket());
        $this->log("disconnect: Code: $statusCode => $reason", $client);
        $this->trigger("disconnect", [
            $client,
            $statusCode,
            $reason
        ]);

        $this->clients = array_filter($this->clients, function (Client $item) use ($client) {
            if ($item->getSocket() == $client->getSocket()) {
                return false;
            }
            return true;
        });

        return $this;
    }

    /**
     * Write message in console
     * @param string $message
     * @param Client|null $client
     */
    private function log($message, Client $client = null)
    {
        if ($this->enableLogging) {
            if (!empty($client)) {
                $clientId = $client->getId() . " => ";
            } else {
                $clientId = null;
            }
            echo date("Y-m-d H:i:s") . ": " . $clientId . $message . "\n";
        }
    }

    /**
     *
     * @return string
     */
    private function generateWebSocketKey()
    {
        $simbols = "1234567890qwertyuiopasdfgjklzxcvbnm";
        return base64_encode(substr(str_shuffle($simbols), 0, 16));
    }

    /**
     * Generate client object from resource
     * @param resource $socket
     * @return Client
     */
    private function createClient($socket)
    {
        $ip = null;
        if (@socket_getpeername($socket, $ip) === false) {
            $this->onError($this->socket);
        }
        $client = new Client();
        $client->setSocket($socket);
        $client->setIp($ip);

        return $client;
    }

    /**
     * Find client by object.
     * @param resource $socket
     * @return Client|null
     */
    private function getClientBySocket($socket)
    {
        foreach ($this->clients as $client) {
            /* @var $client Client */
            if ($client->getSocket() == $socket) {
                return $client;
            }
        }
        return null;
    }

    /**
     * Check if client exist in clients array
     * @param resource $socket
     * @return boolean
     */
    private function checkClientExistBySocket($socket) : bool
    {
        foreach ($this->clients as $client) {
            /* @var $client Client */
            if ($client->getSocket() == $socket) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param array $headers
     * @return boolean
     */
    private function checkOrigin(array $headers) : bool
    {
        if (empty($this->allowedOrigins)) {
            return true;
        }
        if (!empty($headers["Origin"])) {
            $parsedUrl = parse_url($headers["Origin"]);
            foreach ($this->allowedOrigins as $origin) {
                if ($origin == $parsedUrl['host']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *
     * @param string $headers
     * @return array
     */
    private function parseHeaders(string $headers) : array
    {
        $headersArray = explode("\r\n", $headers);
        $array = [];
        if (count($headersArray) > 1) {
            foreach ($headersArray as $header) {
                $headerContentArray = explode(":", $header, 2);
                if (!empty($headerContentArray[1])) {
                    $array[$headerContentArray[0]] = trim($headerContentArray[1]);
                }
            }
        }

        return $array;
    }

    private function sendHttpResponse(Client $client, $httpStatusCode = 400)
    {
        $httpHeader = 'HTTP/1.1 ';
        switch ($httpStatusCode) {
            case 400:
                $httpHeader .= '400 Bad Request';
                break;

            case 401:
                $httpHeader .= '401 Unauthorized';
                break;

            case 403:
                $httpHeader .= '403 Forbidden';
                break;

            case 404:
                $httpHeader .= '404 Not Found';
                break;

            case 501:
                $httpHeader .= '501 Not Implemented';
                break;
        }
        $httpHeader .= "\r\n";
        $this->write($client->getSocket(), $httpHeader);
    }

    private function processClientHandshake(Client $client, $input)
    {
        $matches = [];
        preg_match("/GET (.*) HTTP/i", $input, $matches);
        if (isset($matches[1])) {
            $client->setPath(trim($matches[1]));
        }
        $client->setHeaders($this->parseHeaders($input));

        if (!isset($client->getHeaders()['Sec-WebSocket-Key'])) {
            return false;
        }

        $key = $client->getHeaders()['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        //hand shaking header
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";

        if (!$this->write($client->getSocket(), $upgrade)) {
            $this->log("Handshake can't be sent", $client);
            return false;
        } else {
            $this->log("Handshake sent", $client);
        }

        $client->setHandshake(true);

        return true;
    }

    private function onError($socket)
    {
        $this->log("error: " . $this->getLastErrorMessage(), $this->getClientBySocket($socket));
        $this->trigger("error", [
            $socket,
            $this->getClientBySocket($socket),
            error_get_last(),
            $this->getLastErrorMessage(),
            $this->getLastErrorCode(),
        ]);
//		socket_clear_error($socket);
    }

    private function hybi10Encode(Client $client, $payload, $type = 'text', $masked = false)
    {
        $frameHead = array();
        $frame = '';
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too big)
            if ($frameHead[2] > 127) {
                $this->disconnectClient($client, 1004);
                return false;
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }
        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = array();
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        // append payload to frame:
        $framePayload = array();
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }
        return $frame;
    }

    private function hybi10Decode(Client $client, $data)
    {
        $payloadLength = '';
        $mask = '';
        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;

        // close connection if unmasked frame is received:
        if ($isMasked === false) {
            $this->disconnectClient($client, 1002);
        }

        switch ($opcode) {
            // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;

            // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;

            // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;

            // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                // Close connection on unknown opcode:
                $this->disconnectClient($client, 1003);
                break;
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        if ($isMasked === true) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }

}
