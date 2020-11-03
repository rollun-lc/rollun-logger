<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace ZendTest\Log;

use PHPUnit\Framework\TestCase;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Logger;
use Psr\Log\LogLevel;
use Zend\Log\Writer\WriterInterface;
use Psr\Container\ContainerInterface;
use Zend\Log\Writer\Mock as WriterMock;

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
    public function setUp()
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
        $this->assertContains('test', $this->logWriter->events[0]['message']);
        $this->assertArrayHasKey('id', $this->logWriter->events[0]);
    }
}
