<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace ZendTest\Log;

use PHPUnit\Framework\TestCase;
use rollun\logger\Logger;
use rollun\logger\Processor\IdMaker;
use Psr\Log\LogLevel;
use Psr\Container\ContainerInterface;
use rollun\logger\Formatter\Simple as FormatterSimple;
use rollun\logger\Writer\Stream as WriterStream;

class LoggerWithFileWriterTest extends TestCase
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'csv');
        $this->logger = new Logger([
            'processors' => [
                [
                    'name' => IdMaker::class,
                ],
            ],
            'writers' => [
                [
                    'name' => WriterStream::class,
                    'options' => [
                        'stream' => $this->filename,
                        'formatter' => [
                            'name' => FormatterSimple::class,
                            'format' => '%id% %timestamp% %level% %message% %context%',
                        ],
                    ],
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        unlink($this->filename);
    }

    public function testLoggingArray()
    {
        $this->logger->log(LogLevel::INFO, 'test', [1, 'next', 'key' => 'val']);
        $message = file_get_contents($this->filename);
        $this->assertStringContainsString('test', $message);
        $this->assertStringContainsString('info', $message);
    }
}
