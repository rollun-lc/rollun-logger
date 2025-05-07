<?php

namespace rollun\logger\Transport;

use Exception;

interface TransportInterface
{
    /**
     * Write a message to a buffer
     *
     * @param string $message
     */
    public function write(string $message): void;

    /**
     * Flush messages
     *
     * @throws Exception
     */
    public function flush(): void;

    /**
     * Close transport
     */
    public function close(): void;

    /**
     * Name of transport protocol
     *
     * @return string
     */
    public function getName(): string;
}
