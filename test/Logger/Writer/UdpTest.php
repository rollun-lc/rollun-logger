<?php

namespace rollun\test\logger\Writer;

use ErrorException;
use Exception;
use Jaeger\Transport\TUDPTransport;
use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\Udp;
use RuntimeException;
use rollun\logger\Formatter\FormatterInterface;

class UdpTest extends TestCase
{
    /**
     * @var TUDPTransport
     */
    private $clientMock;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    public function setUp()
    {
        $this->clientMock = $this->createMock(TUDPTransport::class);
        $this->clientMock->expects($this->any())
            ->method('flush')
            ->willThrowException(new Exception('Test exception'));

        $this->formatter = $this->createMock(FormatterInterface::class);
        $this->formatter->expects($this->any())
            ->method('format')
            ->willReturn('test message');
    }

    /**
     * @throws ErrorException
     */
    public function testWriteWithException()
    {
        $udpOptions = [
            'auto_flash' => true,
            'ignore_error' => false,
        ];
        $event = [
            'message' => 'foo',
            'priority' => 42,
        ];
        $object = new Udp($this->clientMock, $udpOptions);
        $object->setFormatter($this->formatter);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error sending messages to Udp');
        $object->write($event);
    }
}
