<?php


namespace rollun\logger\Writer;


use rollun\logger\Transport\Protocol;
use rollun\logger\Transport\StreamSocketTransport;
use rollun\logger\Transport\TransportInterface;

class Tcp extends TransportAbstractWriter
{
    protected function doWrite(array $event)
    {
        parent::doWrite($event);
        // for some reasons tcp socket really write message only when closing
        $this->transport->close();
    }

    function createTransport(string $host, int $port, $options = []): TransportInterface
    {
        return new StreamSocketTransport($host, $port, Protocol::TCP(), $options);
    }
}