<?php

namespace rollun\test\logger\Writer;

use ErrorException;
use Exception;
use PHPUnit\Framework\TestCase;
use rollun\logger\Transport\JagerUDPTransport;
use rollun\logger\Writer\Udp;
use RuntimeException;
use rollun\logger\Formatter\FormatterInterface;

class UdpTest extends TestCase
{
    /**
     * @var Udp
     */
    private $object;
    /**
     * @var JagerUDPTransport
     */
    private $clientMock;

    /**
     * @var FormatterInterface
     */
    private $formatter;

    public function setUp(): void
    {
        $this->clientMock = $this->createMock(JagerUDPTransport::class);
        $this->clientMock->expects($this->any())
            ->method('flush')
            ->willThrowException(new Exception('Test exception'));
        $this->clientMock->method('getName')->willReturn('Udp');

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
        $this->object = new Udp($this->clientMock, $udpOptions);
        $this->object->setFormatter($this->formatter);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error sending messages to Udp');
        $this->object->write($event);
    }
}
