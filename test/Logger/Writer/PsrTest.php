<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger\Writer;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\logger\Filter\Mock as MockFilter;
use Zend\Log\Formatter\Simple as SimpleFormatter;
use rollun\logger\Logger;
use rollun\logger\Writer\Psr as PsrWriter;

/**
 * @coversDefaultClass PsrWriter
 * @covers ::<!public>
 */
class PsrTest extends TestCase
{

    /**
     * @covers ::__construct
     */
    public function testConstructWithPsrLogger()
    {
        $psrLogger = $this->createMock(LoggerInterface::class);
        $writer = new PsrWriter($psrLogger);
        $this->assertAttributeSame($psrLogger, 'logger', $writer);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructWithOptions()
    {
        $psrLogger = $this->createMock(LoggerInterface::class);
        $formatter = new SimpleFormatter();
        $filter = new MockFilter();
        $writer = new PsrWriter([
            'filters' => $filter,
            'formatter' => $formatter,
            'logger' => $psrLogger,
        ]);

        $this->assertAttributeSame($psrLogger, 'logger', $writer);
        $this->assertAttributeSame($formatter, 'formatter', $writer);

        $filters = self::readAttribute($writer, 'filters');
        $this->assertCount(1, $filters);
        $this->assertEquals($filter, $filters[0]);
    }

    /**
     * @covers ::__construct
     */
    public function testFallbackLoggerIsNullLogger()
    {
        $writer = new PsrWriter;
        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $writer);
    }

    /**
     * @dataProvider priorityToLogLevelProvider
     */
    public function testWriteLogMapsLevelsProperly($priority, $logLevel)
    {
        $message = 'foo';
        $context = ['bar' => 'baz'];

        $psrLogger = $this->createMock(LoggerInterface::class);
        $psrLogger->expects($this->once())
                ->method('log')
                ->with(
                        $this->equalTo($logLevel), $this->equalTo($message), $this->equalTo($context)
        );

        $writer = new PsrWriter($psrLogger);
        $logger = new Logger();
        $logger->addWriter($writer);

        $logger->log($priority, $message, $context);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function priorityToLogLevelProvider()
    {
        return [
            [0, LogLevel::EMERGENCY],
            [1, LogLevel::ALERT],
            [2, LogLevel::CRITICAL],
            [3, LogLevel::ERROR],
            [4, LogLevel::WARNING],
            [5, LogLevel::NOTICE],
            [6, LogLevel::INFO],
            [7, LogLevel::DEBUG],
            'emergency' => [LogLevel::EMERGENCY, LogLevel::EMERGENCY],
            'alert' => [LogLevel::ALERT, LogLevel::ALERT],
            'critical' => [LogLevel::CRITICAL, LogLevel::CRITICAL],
            'error' => [LogLevel::ERROR, LogLevel::ERROR],
            'warn' => [LogLevel::WARNING, LogLevel::WARNING],
            'notice' => [LogLevel::NOTICE, LogLevel::NOTICE],
            'info' => [LogLevel::INFO, LogLevel::INFO],
            'debug' => [LogLevel::DEBUG, LogLevel::DEBUG],
        ];
    }

}
