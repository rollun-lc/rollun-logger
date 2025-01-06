<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger;

use PHPUnit\Framework\TestCase;
use rollun\logger\Processor\IdMaker;
use Psr\Log\LogLevel;
use rollun\logger\Writer\WriterInterface;
use Psr\Container\ContainerInterface;
use rollun\logger\Writer\Mock as WriterMock;

class LoggerWriterMockTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     *
     * @var WriterInterface
     */
    protected $logWriter;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $this->container = include 'config/container.php';
        $this->logger = new Logger([
            'processors' => [
                [
                    'name' => IdMaker::class,
                ],
            ],
            'writers' => [
                [
                    'name' => WriterMock::class,
                ],
            ],
        ]);
        $writers = $this->logger->getWriters();
        $this->logWriter = $writers->current();
    }

    public function testLoggingArray()
    {
        $this->logger->log(LogLevel::INFO, ['test']);
        $this->assertEquals(count($this->logWriter->events), 1);
        $this->assertStringContainsString('test', $this->logWriter->events[0]['message']);
        $this->assertArrayHasKey('id', $this->logWriter->events[0]);
    }
}
