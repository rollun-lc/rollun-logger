<?php

namespace rollun\logger\Writer;

use rollun\logger\Transport\Protocol;
use rollun\logger\Transport\StreamSocketTransport;
use rollun\logger\Transport\TransportInterface;

class Udp extends TransportAbstractWriter
{
    public function createTransport(string $host, int $port, $options = []): TransportInterface
    {
        return new StreamSocketTransport($host, $port, Protocol::UDP(), $options);
    }
}
