<?php

namespace rollun\logger\Transport;

use function socket_create;

class UDPTransport extends SocketAbstractTransport
{
    public function getName(): string
    {
        return 'UDP';
    }

    protected function createSocket()
    {
        return socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }
}