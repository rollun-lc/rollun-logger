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
use Zend\Db\TableGateway\TableGateway;

class LoggerWithDbWriterTest extends TestCase
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
     * {@inheritDoc}
     */
    public function setUp()
    {
        global $container;
        $this->container = $container;
        $this->logger = $this->container->get('logWithDbWriter');
        $this->getLogs();
    }

    public function getLogs()
    {
        $adapter = $this->container->get('logDbAdapter');
        $tableGateway = new TableGateway('logs', $adapter);
        $rowset = $tableGateway->select();
        $tableGateway->delete(1);
        return $rowset;
    }

    public function testLoggingArray()
    {
        $this->logger->log(LogLevel::INFO, 'test', [1, 'next', 'key' => 'val']);
        $rowset = $this->getLogs();
        $rowArray = $rowset->toArray();
        $row = $rowArray[0];
        $this->assertArraySubset(['level' => "info", 'priority' => "6", 'message' => "test"], $row);
    }

}
