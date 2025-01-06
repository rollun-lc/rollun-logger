<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\logger\Writer;

use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\Http as HttpWriter;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Http\Response;

class HttpTest extends TestCase
{
    /** @var HttpWriter */
    private $object;

    /**
     * @var Client
     */
    private $clientMock;

    protected $requestRawBody;

    public function setUp(): void
    {
        $this->clientMock = new class extends Client
        {
            protected $rawBodyStorage = null;

            /**
             * Need for save rawBody in our class...
             * @param $rawBodyStorage
             */
            public function setRequestRawBodyStorage(&$rawBodyStorage)
            {
                $this->rawBodyStorage = &$rawBodyStorage;
            }

            public function send(Request $request = null)
            {
                $this->rawBodyStorage = $this->getRequest()
                    ->getContent();

                return new Response();
            }
        };

        $this->clientMock->setRequestRawBodyStorage($this->requestRawBody);
    }

    /**
     * @throws \Exception
     */
    public function testWriteWithoutUri()
    {
        $event = [
            'message' => 'foo',
            'priority' => 42,
        ];
        $this->object = new HttpWriter($this->clientMock);
        $this->object->write($event);
        $rawBody = $this->requestRawBody;
        $this->assertNull($rawBody);
    }

    /**
     * @throws \Exception
     */
    public function testWriteSuccess()
    {
        $event = [
            'message' => 'foo',
            'priority' => 42,
        ];
        $this->object = new HttpWriter($this->clientMock, "http://testuri");
        $this->object->write($event);
        $rawBody = $this->requestRawBody;
        $this->assertNotNull($rawBody);
        $serializedEvent = base64_decode($rawBody);
        $this->assertNotEmpty($serializedEvent);
        $this->assertEquals($event, unserialize($serializedEvent));
    }
}
