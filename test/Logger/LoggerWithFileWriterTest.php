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

class LoggerWithFileWriterTest extends TestCase
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
        if (!is_dir("data/log")) {
            mkdir("data/log", 0777, true);
        }
        $fp = fopen('data/log/test-log.txt', 'w+');
        ftruncate($fp, 0);
        $this->container = include 'config/container.php';
        $this->logger = $this->container->get('logWithFileWriter');
    }

    public function testLoggingArray()
    {
        $this->logger->log(LogLevel::INFO, 'test', [1, 'next', 'key' => 'val']);

        $message = file_get_contents('data/log/test-log.txt');
        //1513891325.338222_ZFUUWSOU 2017-12-22T03:22:05+03:00 info test {"0":1,"1":"next","key":"val"}

        $this->assertContains('test', $message);
        $this->assertContains('info', $message);
    }

}
