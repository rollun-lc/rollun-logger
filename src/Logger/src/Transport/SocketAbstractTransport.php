<?php

namespace rollun\logger\Transport;

use InvalidArgumentException;
use function socket_close;
use function socket_connect;
use function socket_write;
use function strlen;
use function substr;

abstract class SocketAbstractTransport implements TransportInterface
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string
     */
    private $buffer = '';

    /**
     * @var array
     */
    private $options;

    public function __construct(string $host, int $port, array $options = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->options = $options;
    }

    public function isOpen(): bool
    {
        return true;
    }

    public function close(): void
    {
        if (null === $this->socket) {
            return;
        }

        socket_close($this->socket);
        $this->socket = null;
    }

    public function write(string $message): void
    {
        $this->buffer .= $message;
    }

    public function flush(): void
    {
        if ('' === $this->buffer) {
            return;
        }

        $this->doWrite($this->buffer);
        $this->buffer = '';
    }

    private function doWrite($buf)
    {
        $socket = $this->getConnectedSocket();

        $length = strlen($buf);
        while (true) {
            if (false === $result = @socket_write($socket, $buf)) {
                break;
            }
            if ($result >= $length) {
                break;
            }
            $buf = substr($buf, $result);
            $length -= $result;
        }
    }

    /**
     * @return resource|false
     */
    protected function getConnectedSocket()
    {
        if (!isset($this->socket)) {
            if (false !== $socket = $this->createSocket()) {
                $this->setSocketOptions($socket);
                @socket_connect($socket, $this->host, $this->port);
            }

            $this->socket = $socket;
        }

        return $this->socket;
    }

    protected function setSocketOptions($socket): void
    {
        foreach ($this->options as $name => $value) {
            if (false === socket_set_option($socket, SOL_SOCKET, $name, $value))
            {
                throw new InvalidArgumentException("Unable to set socket option {$name} to {$value}.");
            }
        }
    }

    /**
     * @return resource|false
     */
    abstract protected function createSocket();

    abstract public function getName(): string;
}