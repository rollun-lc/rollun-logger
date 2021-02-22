<?php


namespace rollun\logger\Transport;


use Jaeger\Transport\TUDPTransport;

class JagerUDPTransport implements TransportInterface
{
    /**
     * @var TUDPTransport
     */
    private $transport;

    protected $options = [];

    public function __construct(TUDPTransport $transport, $options = [])
    {
        $this->transport = $transport;
        $this->options = $options;
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