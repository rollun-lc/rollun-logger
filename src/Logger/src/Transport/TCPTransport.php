<?php

namespace rollun\logger\Transport;

use function socket_create;

class TCPTransport extends SocketAbstractTransport
{
    public function getName(): string
    {
        return 'TCP';
    }

    protected function createSocket()
    {
        return socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    }
}
