<?php
declare(strict_types=1);

namespace rollun\test\logger\Writer;

use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\HttpAsync;

/**
 * Class HttpAsyncTest
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class HttpAsyncTest extends TestCase
{
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
                'http://some-url.com/path',
                ['foo' => '123'],
                'UE9TVCAvcGF0aCBIVFRQLzEuMQ0KSG9zdDogc29tZS11cmwuY29tDQpBY2NlcHQ6IGFwcGxpY2F0aW9uL2pzb24NCkNvbnRlbnQtVHlwZTogYXBwbGljYXRpb24vanNvbg0KQ29udGVudC1MZW5ndGg6IDEzDQpDb25uZWN0aW9uOiBDbG9zZQ0KDQp7ImZvbyI6IjEyMyJ9ODBzb21lLXVybC5jb20='
            ],
            [
                'http://some-url.com',
                ['foo' => '1'],
                'UE9TVCAvIEhUVFAvMS4xDQpIb3N0OiBzb21lLXVybC5jb20NCkFjY2VwdDogYXBwbGljYXRpb24vanNvbg0KQ29udGVudC1UeXBlOiBhcHBsaWNhdGlvbi9qc29uDQpDb250ZW50LUxlbmd0aDogMTENCkNvbm5lY3Rpb246IENsb3NlDQoNCnsiZm9vIjoiMSJ9ODBzb21lLXVybC5jb20='
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
     * @return HttpAsync
     */
    public function createWriter($options)
    {
        $writer = new class($options) extends HttpAsync {
            /**
             * @var string
             */
            public $hash = '';

            /**
             * @inheritDoc
             */
            protected function send(string $host, int $port, string $out)
            {
                $this->hash = base64_encode($out . (string)$port . $host);
            }
        };

        return $writer;
    }
}
