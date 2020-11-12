<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger\LoggerTest;

use Exception;
use ErrorException;
use InvalidArgumentException;
use PHPUnit\Framework\Error\Warning;
use Psr\Log\LoggerInterface;
use rollun\logger\Writer\Mock;
use rollun\logger\Writer\Noop;
use rollun\logger\WriterPluginManager;
use stdClass;
use TypeError;
use rollun\logger\Logger;
use rollun\logger\Processor\Backtrace;
use rollun\logger\Writer\Mock as MockWriter;
use rollun\logger\Writer\Stream as StreamWriter;
use rollun\logger\Filter\Mock as MockFilter;
use Zend\Stdlib\SplPriorityQueue;
use Zend\Validator\Digits as DigitsFilter;
use Psr\Log\LogLevel;
use Psr\Log\Test\LoggerInterfaceTest;

class LoggerTest extends LoggerInterfaceTest
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MockWriter
     */
    private $mockWriter;

    /**
     * Provides logger for LoggerInterface compat tests
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        $this->mockWriter = new MockWriter;
        $this->logger->addWriter($this->mockWriter);
        return $this->logger;
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
        return array_map(function ($event) {
            $prefix = $event['level'];
            return $prefix . ' ' . $event['message'];
        }, $this->mockWriter->events);
    }

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->logger = new Logger;
    }

    public function testUsesWriterPluginManagerByDefault()
    {
        $this->assertInstanceOf(WriterPluginManager::class, $this->logger->getWriterPluginManager());
    }

    public function testPassingShortNameToPluginReturnsWriterByThatName()
    {
        $writer = $this->logger->writerPlugin('mock');
        $this->assertInstanceOf(Mock::class, $writer);
    }

    public function testPassWriterAsString()
    {
        $this->logger->addWriter('mock');
        $writers = $this->logger->getWriters();
        $this->assertInstanceOf('Zend\Stdlib\SplPriorityQueue', $writers);
    }

    public function testEmptyWriter()
    {
        // expect trigger_error() with E_USER_WARNING
        $this->expectException(Warning::class);
        $this->expectExceptionMessage('No log writer was specified.');
        $this->logger->log(LogLevel::INFO, 'test');
    }

    public function testSetWriters()
    {
        $writer1 = $this->logger->writerPlugin('mock');
        $writer2 = $this->logger->writerPlugin('noop');
        $writers = new SplPriorityQueue();
        $writers->insert($writer1, 1);
        $writers->insert($writer2, 2);
        $this->logger->setWriters($writers);

        $writers = $this->logger->getWriters();
        $this->assertInstanceOf('Zend\Stdlib\SplPriorityQueue', $writers);
        $writer = $writers->extract();
        $this->assertInstanceOf(Noop::class, $writer);
        $writer = $writers->extract();
        $this->assertInstanceOf(Mock::class, $writer);
    }

    public function testAddWriterWithPriority()
    {
        $writer1 = $this->logger->writerPlugin('mock');
        $this->logger->addWriter($writer1, 1);
        $writer2 = $this->logger->writerPlugin('noop');
        $this->logger->addWriter($writer2, 2);
        $writers = $this->logger->getWriters();

        $this->assertInstanceOf('Zend\Stdlib\SplPriorityQueue', $writers);
        $writer = $writers->extract();
        $this->assertInstanceOf(Noop::class, $writer);
        $writer = $writers->extract();
        $this->assertInstanceOf(Mock::class, $writer);
    }

    public function testAddWithSamePriority()
    {
        $writer1 = $this->logger->writerPlugin('mock');
        $this->logger->addWriter($writer1, 1);
        $writer2 = $this->logger->writerPlugin('noop');
        $this->logger->addWriter($writer2, 1);
        $writers = $this->logger->getWriters();

        $this->assertInstanceOf('Zend\Stdlib\SplPriorityQueue', $writers);
        $writer = $writers->extract();
        $this->assertInstanceOf(Mock::class, $writer);
        $writer = $writers->extract();
        $this->assertInstanceOf(Noop::class, $writer);
    }

    public function testLogging()
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log(LogLevel::INFO, 'tottakai');

        $this->assertCount(1, $writer->events);
        $this->assertContains('tottakai', $writer->events[0]['message']);
    }

    public function testLoggingArray()
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log(LogLevel::INFO, 'test');

        $this->assertCount(1, $writer->events);
        $this->assertContains('test', $writer->events[0]['message']);
    }

    public function testAddFilter()
    {
        $writer = new MockWriter;
        $filter = new MockFilter;
        $writer->addFilter($filter);
        $this->logger->addWriter($writer);
        $this->logger->log(LogLevel::INFO, 'test');

        $this->assertCount(1, $filter->events);
        $this->assertContains('test', $filter->events[0]['message']);
    }

    public function testAddFilterByName()
    {
        $writer = new MockWriter;
        $writer->addFilter('mock');
        $this->logger->addWriter($writer);
        $this->logger->log(LogLevel::INFO, 'test');

        $this->assertCount(1, $writer->events);
        $this->assertContains('test', $writer->events[0]['message']);
    }

    /**
     * provideTestFilters
     */
    public function provideTestFilters()
    {
        // TODO:: Change priority to constant (like Logger::INFO)
        $data = [
            ['priority', ['priority' => 6]],
            ['regex', ['regex' => '/[0-9]+/']],
        ];

        // Conditionally enabled until zend-validator is forwards-compatible
        // with zend-servicemanager v3.
        if (class_exists(DigitsFilter::class)) {
            $data[] = ['validator', ['validator' => new DigitsFilter]];
        }

        return $data;
    }

    /**
     * @dataProvider provideTestFilters
     * @param $filter
     * @param $options
     */
    public function testAddFilterByNameWithParams($filter, $options)
    {
        $writer = new MockWriter;
        $writer->addFilter($filter, $options);
        $this->logger->addWriter($writer);

        $this->logger->log(6, '123');
        $this->assertCount(1, $writer->events);
        $this->assertContains('123', $writer->events[0]['message']);
    }

    public static function provideAttributes()
    {
        return [
            [[]],
            [['user' => 'foo', 'ip' => '127.0.0.1']],
            [[['id' => 42]]],
        ];
    }

    /**
     * @dataProvider provideAttributes
     * @param $context
     */
    public function testLoggingCustomAttributesForUserContext($context)
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log(LogLevel::ERROR, 'tottakai', $context);

        $this->assertCount(1, $writer->events);
        $this->assertInternalType('array', $writer->events[0]['context']);
        $this->assertSameSize($writer->events[0]['context'], $context);
    }

    public static function provideInvalidArguments()
    {
        return [
            [new stdClass(), ['valid'], InvalidArgumentException::class],
            ['valid', true, TypeError::class],
            ['valid', 10, TypeError::class],
            ['valid', 'invalid', TypeError::class],
        ];
    }

    /**
     * @dataProvider provideInvalidArguments
     * @param $message
     * @param $context
     * @param $exc
     */
    public function testPassingInvalidArgumentToLogRaisesException($message, $context, $exc)
    {
        $this->expectException($exc);
        $this->logger->log(LogLevel::ERROR, $message, $context);
    }

    public function testRegisterErrorHandler()
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);

        $previous = Logger::registerErrorHandler($this->logger);
        $this->assertNotNull($previous);
        $this->assertNotFalse($previous);

        // check for single error handler instance
        $this->assertFalse(Logger::registerErrorHandler($this->logger));

        $level = error_reporting();
        error_reporting(E_ALL);

        // generate a warning
        echo $test; // $test is not defined

        error_reporting($level);
        Logger::unregisterErrorHandler();

        $this->assertEquals('Undefined variable: test', $writer->events[0]['message']);
    }

    public function testOptionsWithMock()
    {
        $options = ['writers' => [
            'first_writer' => [
                'name' => 'mock',
            ]
        ]];
        $logger = new Logger($options);

        $writers = $logger->getWriters()->toArray();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(Mock::class, $writers[0]);
    }

    public function testOptionsWithWriterOptions()
    {
        $options = ['writers' => [
            [
                'name' => 'stream',
                'options' => [
                    'stream' => 'php://output',
                    'log_separator' => 'foo'
                ],
            ]
        ]];
        $logger = new Logger($options);

        $writers = $logger->getWriters()->toArray();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(StreamWriter::class, $writers[0]);
        $this->assertEquals('foo', $writers[0]->getLogSeparator());
    }

    public function testOptionsWithMockAndProcessor()
    {
        $options = [
            'writers' => [
                'first_writer' => [
                    'name' => 'mock',
                ],
            ],
            'processors' => [
                'first_processor' => [
                    'name' => 'backtrace',
                ],
            ]
        ];
        $logger = new Logger($options);
        $processors = $logger->getProcessors()->toArray();
        $this->assertCount(1, $processors);
        $this->assertInstanceOf(Backtrace::class, $processors[0]);
    }

    public function testAddProcessor()
    {
        $processor = new Backtrace();
        $this->logger->addProcessor($processor);

        $processors = $this->logger->getProcessors()->toArray();
        $this->assertEquals($processor, $processors[0]);
    }

    public function testAddProcessorByName()
    {
        $this->logger->addProcessor('backtrace');

        $processors = $this->logger->getProcessors()->toArray();
        $this->assertInstanceOf(Backtrace::class, $processors[0]);

        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log(LogLevel::ERROR, 'foo');
    }

    public function testProcessorOutputAdded()
    {
        $processor = new Backtrace();
        $this->logger->addProcessor($processor);
        $writer = new MockWriter;
        $this->logger->addWriter($writer);

        $this->logger->log(LogLevel::ERROR, 'foo');
        $this->assertEquals(__FILE__, $writer->events[0]['context']['file']);
    }

    public function testExceptionHandler()
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);

        $this->assertTrue(Logger::registerExceptionHandler($this->logger));

        // check for single error handler instance
        $this->assertFalse(Logger::registerExceptionHandler($this->logger));

        // get the internal exception handler
        $exceptionHandler = set_exception_handler(function ($e) {

        });
        set_exception_handler($exceptionHandler);

        // reset the exception handler
        Logger::unregisterExceptionHandler();

        // call the exception handler
        $exceptionHandler(new Exception('error', 200, new Exception('previos', 100)));
        $exceptionHandler(new ErrorException('user notice', 1000, E_USER_NOTICE, __FILE__, __LINE__));

        // check logged messages
        $expectedEvents = [
            ['level' => LogLevel::ERROR, 'message' => 'previos', 'file' => __FILE__],
            ['level' => LogLevel::ERROR, 'message' => 'error', 'file' => __FILE__],
            ['level' => LogLevel::NOTICE, 'message' => 'user notice', 'file' => __FILE__],
        ];
        for ($i = 0; $i < count($expectedEvents); $i++) {
            $expectedEvent = $expectedEvents[$i];
            $event = $writer->events[$i];

            $this->assertEquals($expectedEvent['level'], $event['level'], 'Unexpected priority');
            $this->assertEquals($expectedEvent['message'], $event['message'], 'Unexpected message');
            $this->assertEquals($expectedEvent['file'], $event['context']['file'], 'Unexpected file');
        }
    }

    public function testLogExtraArrayKeyWithNonArrayValue()
    {
        $stream = fopen("php://memory", "r+");
        $options = [
            'writers' => [
                [
                    'name' => 'stream',
                    'options' => [
                        'stream' => $stream
                    ],
                ],
            ],
        ];
        $logger = new Logger($options);

        $logger->info('Hi', ['context' => '']);
        $contents = stream_get_contents($stream, -1, 0);
        $this->assertContains('Hi', $contents);
        fclose($stream);
    }

    /**
     * @group 5383
     */
    public function testErrorHandlerWithStreamWriter()
    {

        $options = ['errorhandler' => true];
        $logger = new Logger($options);
        $stream = fopen('php://memory', 'w+');
        $streamWriter = new StreamWriter($stream);

        // error handler does not like this feature so turn it off
        $streamWriter->setConvertWriteErrorsToExceptions(false);
        $logger->addWriter($streamWriter);

        $level = error_reporting();
        error_reporting(E_ALL);
        // we raise two notices - both should be logged
        echo $test;
        echo $second;
        error_reporting($level);

        rewind($stream);
        $contents = stream_get_contents($stream);
        $this->assertContains('test', $contents);
        $this->assertContains('second', $contents);
    }

    /**
     * @group ZF2-7238
     */
    public function testCatchExceptionNotValidPriority()
    {
        $this->expectException('Psr\Log\InvalidArgumentException');
        $this->expectExceptionMessage('$level must be one of PSR-3 log levels; received -1');
        $writer = new MockWriter();
        $this->logger->addWriter($writer);
        $this->logger->log(-1, 'Foo');
    }

    /**
     * @dataProvider priorityToLogLevelProvider
     * @param $priorityLevel
     * @param $logLevel
     * @param $priority
     */
    public function testWriteLogMapsLevelsProperly($priorityLevel, $logLevel, $priority)
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log($priorityLevel, 'tottakai');

        $this->assertCount(1, $writer->events);
        $this->assertEquals($logLevel, $writer->events[0]['level']);
        $this->assertEquals($priority, $writer->events[0]['priority']);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function priorityToLogLevelProvider()
    {
        return [
            [0, LogLevel::EMERGENCY, 0],
            [1, LogLevel::ALERT, 1],
            [2, LogLevel::CRITICAL, 2],
            [3, LogLevel::ERROR, 3],
            [4, LogLevel::WARNING, 4],
            [5, LogLevel::NOTICE, 5],
            [6, LogLevel::INFO, 6],
            [7, LogLevel::DEBUG, 7],
            [LogLevel::EMERGENCY, LogLevel::EMERGENCY, 0],
            [LogLevel::ALERT, LogLevel::ALERT, 1],
            [LogLevel::CRITICAL, LogLevel::CRITICAL, 2],
            [LogLevel::ERROR, LogLevel::ERROR, 3],
            [LogLevel::WARNING, LogLevel::WARNING, 4],
            [LogLevel::NOTICE, LogLevel::NOTICE, 5],
            [LogLevel::INFO, LogLevel::INFO, 6],
            [LogLevel::DEBUG, LogLevel::DEBUG, 7],
        ];
    }

}
