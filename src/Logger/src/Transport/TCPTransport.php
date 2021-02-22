<?php


namespace rollun\logger\Transport;


use function socket_close;
use function socket_connect;
use function socket_create;
use function socket_write;
use function strlen;
use function substr;

class TCPTransport implements TransportInterface
{
    private $host;

    private $port;

    /**
     * @var resource
     */
    private $socket;

    private $buffer = '';

    protected $options = [];

    public function __construct(string $host, int $port, $options = [])
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

    private function getConnectedSocket()
    {
        if (null === $this->socket) {
            if (false !== $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
                foreach ($this->options as $name => $value) {
                    socket_set_option($socket, SOL_SOCKET, $name, $value);
                }
                @socket_connect($socket, $this->host, $this->port);
            }

            $this->socket = $socket;
        }

        return $this->socket;
    }

    public function getName(): string
    {
        return 'TCP';
    }
}