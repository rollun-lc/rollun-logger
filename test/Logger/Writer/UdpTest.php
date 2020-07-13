<?php

namespace rollun\test\logger\Writer;

use RuntimeException;
use Jaeger\Transport\TUDPTransport;
use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\Udp;

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

    public function setUp()
    {
        $this->clientMock = $this->createMock(TUDPTransport::class);
        $this->clientMock->expects($this->any())
            ->method('write')
            ->willThrowException(new \Exception('Get exception'));
    }

    public function testDoWriteWithException()
    {
        $udpOptions = [
            'formatter' => 'Formatter',
        ];
        $event = [
            'message' => 'foo',
            'priority' => 42,
        ];
        $this->object = new Udp($this->clientMock, $udpOptions);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Error sending messages to Udp. Total attempts: %s', Udp::MAX_ATTEMPTS));
        $this->object->write($event);
    }
}
