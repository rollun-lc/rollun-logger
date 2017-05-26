<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 19.12.16
 * Time: 11:43 AM
 */
namespace rollun\test\res\Logger;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\LoggerInterfaceTest;
use rollun\dic\InsideConstruct;
use rollun\logger\Logger;
use rollun\logger\LogWriter\FileLogWriter;
use rollun\logger\LogWriter\FileLogWriterFactory;

class LoggerTest extends LoggerInterfaceTest
{
    /** @var  LoggerInterface */
    protected $object;

    /** @var  ContainerInterface */
    protected $container;

    protected $file;

    public function setUp()
    {
        $this->container = include 'config/container.php';
        InsideConstruct::setContainer($this->container);
        $this->object = $this->getLogger();
        $config = $this->container->get('config');
        $this->file = FileLogWriterFactory::getLogFile();
        fopen($this->file, "w");
    }

    /**
     * @dataProvider provideLogDateTime
     * @param $dateTime
     * @param $expectedTime
     */
    public function testLogWithTime($dateTime, $expectedTime)
    {
        $this->object->log(
            LogLevel::ERROR,
            $dateTime . "|" . "Error message of level emergency with context: {user}",
            ['user' => 'Bob']
        );
        $expected = [
            $expectedTime . ' ' . LogLevel::ERROR . ' ' .
            'Error message of level emergency with context: Bob'
        ];
        $this->assertEquals($expected, $this->getLogsWithTime());
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return new Logger();
    }

    public function provideLogDateTime()
    {
        $time = new \DateTime();
        return [
            [$time->format('Y-m-d H:i:s'), $time->getTimestamp()],
            [$time->format('D M j G:i:s T Y'), $time->getTimestamp()],
            [$time->getTimestamp(), $time->getTimestamp()],
        ];
    }

    /**
     * This must return the log messages in order.
     *
     * The simple formatting of the messages is: "<LOG LEVEL> <MESSAGE>".
     *
     * Example ->error('Foo') would yield "error Foo".
     *
     * @return string[]
     */
    public function getLogs()
    {
        $logs = [];
        $logsString = array_diff(explode("\n", file_get_contents($this->file)), [""]);
        foreach ($logsString as $log) {
            $part = explode(';', $log);
            $logs[] = $part[1] . ' ' . trim($part[2], '"');
        }
        return $logs;
    }

    public function getLogsWithTime()
    {
        $logs = [];
        $logsString = array_diff(explode("\n", file_get_contents($this->file)), [""]);
        foreach ($logsString as $log) {
            $part = explode(';', $log);
            $logs[] = explode('_', base64_decode($part[0]))[1] . ' ' . $part[1] . ' ' . trim($part[2], '"');
        }
        return $logs;
    }
}
