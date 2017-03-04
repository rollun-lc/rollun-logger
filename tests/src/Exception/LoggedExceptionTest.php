<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12.01.17
 * Time: 16:32
 */

namespace rollun\test\logger\Exception;

use rollun\dic\InsideConstruct;
use rollun\installer\Command;
use rollun\logger\LogWriter;
use rollun\logger\Exception\LoggedException;
use rollun\logger\LogWriter\FileLogWriterInterface;
use rollun\logger\LogWriter\FileLogWriterFactory;
use rollun\utils\Json\Serializer;
use Zend\Stdlib\Exception\BadMethodCallException;

class LoggedExceptionTest extends \PHPUnit_Framework_TestCase
{

    protected $logFile;


    public function setUp()
    {
        $container = include 'config/container.php';
        InsideConstruct::setContainer($container);
        $this->logFile =
            $container->get('config')['logWriter'][FileLogWriterInterface::class][FileLogWriterFactory::FILE_NAME_KEY];
        fopen($this->logFile, "w");
    }

    public function exceptionDataProvider()
    {
        $exception = new LoggedException("Error root LoggedException");
        return [
            [$exception],
        ];
    }


    public function testException()
    {
        try {
            throw new LoggedException("Error root LoggedException");
        } catch (LoggedException $e) {
            $nestdLevel = $this->getExceptionNestedLevel($e, 1);
            $str = file_get_contents($this->logFile);
            $logsString = array_diff(explode("\n", $str), [""]);
            $this->assertEquals($nestdLevel, count($logsString));
            $serialize = Serializer::jsonSerialize($e);
            $unserializeException = Serializer::jsonUnserialize($serialize);
            $this->assertEquals($e, $unserializeException);
        }
    }

    public function testNestedSTLException()
    {
        try {
            throw new LoggedException(
                "Error root LoggedException",
                0,
                new \RuntimeException('Error nested STL')
            );
        } catch (LoggedException $e) {
            $nestdLevel = $this->getExceptionNestedLevel($e, 1);
            $str = file_get_contents($this->logFile);
            $logsString = array_diff(explode("\n", $str), [""]);
            $this->assertEquals($nestdLevel, count($logsString));
            $serialize = Serializer::jsonSerialize($e);
            $unserializeException = Serializer::jsonUnserialize($serialize);
            $this->assertEquals($e, $unserializeException);
        }
    }

    public function testNestedCustomException()
    {
        try {
            throw  new LoggedException(
                "Error root LoggedException",
                0,
                new BadMethodCallException('Error nested BadMethodCallException')
            );
        } catch (LoggedException $e) {
            $nestdLevel = $this->getExceptionNestedLevel($e, 1);
            $str = file_get_contents($this->logFile);
            $logsString = array_diff(explode("\n", $str), [""]);
            $this->assertEquals($nestdLevel, count($logsString));
            $serialize = Serializer::jsonSerialize($e);
            $unserializeException = Serializer::jsonUnserialize($serialize);
            $this->assertEquals($e, $unserializeException);
        }
    }

    public function testTwoNestedException()
    {
        try {
            throw new LoggedException(
                "Error root LoggedException",
                0,
                new \RuntimeException(
                    'Error nested STL',
                    1,
                    new BadMethodCallException('Error nested BadMethodCallException')
                )
            );
        } catch (LoggedException $e) {
            $nestdLevel = $this->getExceptionNestedLevel($e, 1);
            $str = file_get_contents($this->logFile);
            $logsString = array_diff(explode("\n", $str), [""]);
            $this->assertEquals($nestdLevel, count($logsString));
            $serialize = Serializer::jsonSerialize($e);
            $unserializeException = Serializer::jsonUnserialize($serialize);
            $this->assertEquals($e, $unserializeException);
        }
    }

    public function testTreeNestedException()
    {
        try {
            throw new LoggedException(
                "Error root LoggedException",
                0,
                new \RuntimeException(
                    'Error nested LoggedException',
                    1,
                    new LoggedException(
                        'Error nested STL',
                        1,
                        new BadMethodCallException('Error nested BadMethodCallException')
                    )
                )
            );
        } catch (LoggedException $e) {
            $nestdLevel = $this->getExceptionNestedLevel($e, 1);
            $str = file_get_contents($this->logFile);
            $logsString = array_diff(explode("\n", $str), [""]);
            $this->assertEquals($nestdLevel, count($logsString));
            $serialize = Serializer::jsonSerialize($e);
            $unserializeException = Serializer::jsonUnserialize($serialize);
            $this->assertEquals($e, $unserializeException);
        }
    }

    protected function getExceptionNestedLevel(\Exception $exception, $level)
    {
        if ($exception->getPrevious() != null) {
            $level = $this->getExceptionNestedLevel($exception->getPrevious(), $level + 1);
        }
        return $level;
    }
}
