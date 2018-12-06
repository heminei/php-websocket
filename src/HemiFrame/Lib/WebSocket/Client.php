<?php

namespace HemiFrame\Lib\WebSocket;

/**
 * @author Heminei
 * @link https://github.com/heminei/php-websocket
 */
class Client
{

    private $id;
    private $socket;
    private $handshake = false;
    private $ip;
    private $headers = [];
    private $path = "/";

    public function __construct()
    {
        $this->id = uniqid("wsc");
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function getHandshake() : bool
    {
        return $this->handshake;
    }

    public function getIp() : string
    {
        return $this->ip;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getHeader(string $name)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        return null;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setId(string $id) : self
    {
        $this->id = $id;
        return $this;
    }

    public function setSocket($socket) : self
    {
        $this->socket = $socket;
        return $this;
    }

    public function setHandshake(bool $handshake) : self
    {
        $this->handshake = $handshake;
        return $this;
    }

    public function setIp(string $ip) : self
    {
        $this->ip = $ip;
        return $this;
    }

    public function setHeaders(array $headers) : self
    {
        $this->headers = $headers;
        return $this;
    }

    public function setHeader(string $name, string $value) : self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setPath(string $path) : self
    {
        $this->path = $path;
        return $this;
    }

}
