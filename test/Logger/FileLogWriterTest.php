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
use rollun\logger\LogWriter\FileLogWriter;
use Zend\Stdlib\SplPriorityQueue;
use Zend\Validator\Digits as DigitsFilter;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

class FileLogWriterTest extends TestCase
{

    /**
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var FileLogWriter
     */
    protected $fileLogWriter;

    protected $fileName = 'data/log/test-log.txt';

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
        $this->fileLogWriter = new FileLogWriter($this->fileName);
    }

    public function testQutedMessage()
    {
    	$message = 'test with " quote " ';
    	$this->fileLogWriter->logWrite("test_id_test", "info",$message);
        $message = file_get_contents($this->fileName);

        $this->assertEquals("test_id_test;info;\"test with \"\" quote \"\" \"\n", $message);
    }
}
