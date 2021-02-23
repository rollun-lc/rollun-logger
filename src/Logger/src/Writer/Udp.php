<?php


namespace rollun\logger\Writer;

use InvalidArgumentException;
use rollun\logger\Transport\TransportInterface;
use rollun\logger\Transport\UDPTransport;

class Udp extends TransportAbstractWriter
{
    public function __construct($transport, array $options = [])
    {
        parent::__construct($transport, $options);

        if (!$this->transport instanceof TransportInterface) {
            throw new InvalidArgumentException('You must pass a valid ' . TransportInterface::class);
        }
    }

    function createTransport(string $host, int $port, $options = []): TransportInterface
    {
        return new UDPTransport($host, $port, $options);
    }
}
