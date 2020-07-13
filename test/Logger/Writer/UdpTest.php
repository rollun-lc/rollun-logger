<?php

namespace rollun\test\logger\Writer;

use RuntimeException;
use Jaeger\Transport\TUDPTransport;
use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\Udp;
use Zend\Log\Formatter\FormatterInterface;

class UdpTest extends TestCase
{
    /**
     * @var Udp
     */
    private $object;
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
            ->willThrowException(new \Exception('Test exception'));

        $this->formatter = $this->createMock(FormatterInterface::class);
        $this->formatter->expects($this->any())
            ->method('format')
            ->willReturn('test message');
    }

    public function testDoWriteWithException()
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
        $this->expectExceptionMessage(sprintf('Error sending messages to Udp. Total attempts: %s', Udp::MAX_ATTEMPTS));
        $this->object->write($event);
    }
}
