<?php


namespace rollun\logger\Writer;


use InvalidArgumentException;
use rollun\logger\Transport\TCPTransport;
use rollun\logger\Transport\TransportInterface;

class Tcp extends TransportAbstractWriter
{
    public function __construct($transport, array $options = [])
    {
        parent::__construct($transport, $options);

        if (!$this->transport instanceof TCPTransport) {
            throw new InvalidArgumentException('You must pass a valid rollun\logger\TCPTransport');
        }
    }

    function createTransport(string $host, int $port): TransportInterface
    {
        return new TCPTransport($host, $port);
    }
}