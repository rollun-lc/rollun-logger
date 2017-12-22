<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Log;

use Exception;
use ErrorException;
use PHPUnit\Framework\TestCase;
use Zend\Log\Exception\RuntimeException;
use Zend\Log\Logger;
use Zend\Log\Processor\Backtrace;
use Zend\Log\Writer\Mock as MockWriter;
use Zend\Log\Writer\Stream as StreamWriter;
use Zend\Log\Filter\Mock as MockFilter;
use Zend\Stdlib\SplPriorityQueue;
use Zend\Validator\Digits as DigitsFilter;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Zend\Log\Writer\WriterInterface;
use Psr\Container\ContainerInterface;
use Zend\Log\Writer\Mock as WriterMock;

class LoggerWriterMockTest extends TestCase
{

    /**
     *
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
        //InsideConstruct::setContainer($this->container);
        $this->logger = $this->container->get('logWithMockWriter');
        $writers = $this->logger->getWriters();
        $this->logWriter = $writers->extract();
        $this->logger->addWriter($this->logWriter);
    }

    public function testLoggingArray()
    {

        $this->logger->log(LogLevel::INFO, ['test']);
        $this->assertEquals(count($this->logWriter->events), 1);

        $this->assertContains('test', $this->logWriter->events[0]['message']);
        $this->assertArrayHasKey('id', $this->logWriter->events[0]);
    }

}
