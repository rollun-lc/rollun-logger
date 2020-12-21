<?php


namespace rollun\logger\Transport;


use Jaeger\Transport\TUDPTransport;

class JagerUDPTransport implements TransportInterface
{
    /**
     * @var TUDPTransport
     */
    private $transport;

    public function __construct(TUDPTransport $transport)
    {
        $this->transport = $transport;
    }

    public function write(string $message): void
    {
        $this->transport->write($message);
    }

    public function flush(): void
    {
        $this->transport->flush();
    }

    public function close(): void
    {
        $this->transport->close();
    }

    public function getName(): string
    {
        return 'UDP';
    }
}