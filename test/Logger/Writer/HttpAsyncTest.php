<?php

declare(strict_types=1);

namespace Rollun\Test\Logger\Writer;

use PHPUnit\Framework\TestCase;
use rollun\logger\LifeCycleToken;
use rollun\logger\Writer\HttpAsync;

/**
 * Class HttpAsyncTest
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class HttpAsyncTest extends TestCase
{
    private const TEST_URL = 'http://some-url.com/path';

    private const TEST_HOST = 'some-url.com';

    private const TEST_LIFECYCLE_TOKEN = 'TRZ37KHQPXGLU7KM00Z2RVBYDU3ZR5';

    /**
     * Write with invalid URL test
     */
    public function testWriteWithInvalidUrl()
    {
        try {
            $writer = $this->createWriter(['url' => 'asdsad sad']);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(), 'URL is invalid');
        }
    }

    /**
     * @return array[]
     */
    public function getWriteSuccessProvider()
    {
        // $url, $data, $expected
        return [
            [
                self::TEST_URL,
                ['foo' => '123'],
                'UE9TVCAvcGF0aCBIVFRQLzEuMQ0KSG9zdDogc29tZS11cmwuY29tDQpBY2NlcHQ6IGFwcGxpY2F0aW9uL2pzb24NCkNvbnRlbnQtVHlwZTogYXBwbGljYXRpb24vanNvbg0KQ29udGVudC1MZW5ndGg6IDEzDQpDb25uZWN0aW9uOiBDbG9zZQ0KDQp7ImZvbyI6IjEyMyJ9ODBzb21lLXVybC5jb20=',
            ],
            [
                'http://some-url.com',
                ['foo' => '1'],
                'UE9TVCAvIEhUVFAvMS4xDQpIb3N0OiBzb21lLXVybC5jb20NCkFjY2VwdDogYXBwbGljYXRpb24vanNvbg0KQ29udGVudC1UeXBlOiBhcHBsaWNhdGlvbi9qc29uDQpDb250ZW50LUxlbmd0aDogMTENCkNvbm5lY3Rpb246IENsb3NlDQoNCnsiZm9vIjoiMSJ9ODBzb21lLXVybC5jb20=',
            ],
        ];
    }

    /**
     * Write success test
     *
     * @dataProvider getWriteSuccessProvider
     */
    public function testWriteSuccess($url, $data, $expected)
    {
        $writer = $this->createWriter(['url' => $url]);
        $writer->write($data);

        $this->assertEquals($expected, $writer->hash);
    }

    /**
     * @param array $options
     * @return HttpAsync
     */
    public function createWriter(array $options): HttpAsync
    {
        $writer = new class ($options) extends HttpAsync {
            public $hash = '';

            protected function send(string $host, int $port, string $out)
            {
                $this->hash = base64_encode($out . (string) $port . $host);
            }
        };

        return $writer;
    }

    public function testAddsLifeCycleTokenHeader()
    {
        $writer = $this->createWriter(['url' => self::TEST_URL]);

        $event = [
            LifeCycleToken::KEY_LIFECYCLE_TOKEN => self::TEST_LIFECYCLE_TOKEN,
            'context' => ['any' => 'thing'],
        ];

        $writer->write($event);

        $raw = base64_decode($writer->hash, true);
        $this->assertNotFalse($raw, 'Base64 decode failed');

        $this->assertStringContainsString("LifeCycleToken: " . self::TEST_LIFECYCLE_TOKEN . "\r\n", $raw);
        $this->assertStringContainsString("POST /path HTTP/1.1\r\n", $raw);
        $this->assertStringContainsString("Host: " . self::TEST_HOST . "\r\n", $raw);
    }

    public function testIsValidFalseSkipsSend()
    {
        $writer = new class (['url' => 'http://some-url.com']) extends HttpAsync {
            public $sendCalled = false;

            protected function isValid(array $event): bool
            {
                return false;
            }

            protected function send(string $host, int $port, string $out)
            {
                $this->sendCalled = true;
            }
        };

        $writer->write(['context' => ['foo' => 'bar']]);

        $this->assertFalse($writer->sendCalled, 'send() must not be called when isValid() === false');
    }

    /**
     * @return array[]
     */
    public function getHttpErrorProvider(): array
    {
        return [
            '400 Bad Request' => [400, 'Bad Request'],
            '500 Internal Server Error' => [500, 'Internal Server Error'],
            '503 Service Unavailable' => [503, 'Service Unavailable'],
        ];
    }

    /**
     * @dataProvider getHttpErrorProvider
     */
    public function testThrowsOnSendError(int $statusCode, string $statusText)
    {
        $writer = new class (self::TEST_URL, $statusCode, $statusText) extends HttpAsync {
            public function __construct(string $url, private int $statusCode = 400, private string $statusText = 'Bad Request')
            {
                parent::__construct(['url' => $url]);
            }

            protected function send(string $host, int $port, string $out)
            {
                throw new \RuntimeException("Server returned error {$this->statusCode} for {$this->url}");
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("error {$statusCode}");

        $writer->write(['context' => ['foo' => 'bar']]);
    }
}
