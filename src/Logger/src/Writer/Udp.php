<?php


namespace rollun\logger\Writer;

use InvalidArgumentException;
use Jaeger\Transport\TUDPTransport;
use rollun\logger\Transport\JagerUDPTransport;
use rollun\logger\Transport\TransportInterface;

class Udp extends TransportAbstractWriter
{
    public function __construct($transport, array $options = [])
    {
        parent::__construct($transport, $options);

        if (!$this->transport instanceof JagerUDPTransport) {
            throw new InvalidArgumentException('You must pass a valid rollun\logger\JagerUDPTransport');
        }
    }

    function createTransport(string $host, int $port): TransportInterface
    {
        return new JagerUDPTransport(new TUDPTransport($host, $port));
    }
}
