<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger\Formatter;

use DateTime;
use PHPUnit\Framework\TestCase;
use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Formatter\Simple;
use RuntimeException;
use Psr\Log\LogLevel;

class SimpleTest extends TestCase
{

    public function testConstructorThrowsOnBadFormatString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a string');
        new Simple(1);
    }

    /**
     * @dataProvider provideDateTimeFormats
     */
    public function testConstructorWithOptions($dateTimeFormat)
    {
        $options = ['dateTimeFormat' => $dateTimeFormat, 'format' => '%timestamp%'];
        $formatter = new Simple($options);

        $this->assertEquals($dateTimeFormat, $formatter->getDateTimeFormat());
        $reflectionProperty = new \ReflectionProperty($formatter, 'format');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($formatter);
        $this->assertEquals('%timestamp%', $value);
        //$this->assertAttributeEquals('%timestamp%', 'format', $formatter);
    }

    public function testDefaultFormat()
    {
        $date = new DateTime('2012-08-28T18:15:00Z');
        $fields = [
            'timestamp' => $date,
            'message' => 'foo',
            'priority' => 42,
            'level' => 'bar',
            'context' => []
        ];

        $outputExpected = '2012-08-28T18:15:00+00:00 bar (42): foo';
        $formatter = new Simple();

        $this->assertEquals($outputExpected, $formatter->format($fields));
    }

    /**
     * @dataProvider provideDateTimeFormats
     */
    public function testCustomDateTimeFormat($dateTimeFormat)
    {
        $date = new DateTime();
        $event = ['timestamp' => $date];
        $formatter = new Simple('%timestamp%', $dateTimeFormat);

        $this->assertEquals($date->format($dateTimeFormat), $formatter->format($event));
    }

    /**
     * @dataProvider provideDateTimeFormats
     */
    public function testSetDateTimeFormat($dateTimeFormat)
    {
        $date = new DateTime();
        $event = ['timestamp' => $date];
        $formatter = new Simple('%timestamp%');

        $this->assertSame($formatter, $formatter->setDateTimeFormat($dateTimeFormat));
        $this->assertEquals($dateTimeFormat, $formatter->getDateTimeFormat());
        $this->assertEquals($date->format($dateTimeFormat), $formatter->format($event));
    }

    public function provideDateTimeFormats()
    {
        return [
            ['r'],
            ['U'],
        ];
    }

    /**
     * @group ZF-10427
     */
    public function testDefaultFormatShouldDisplayExtraInformations()
    {
        $message = 'custom message';
        $exception = new RuntimeException($message);
        $event = [
            'timestamp' => new DateTime(),
            'message' => 'Application error',
            'priority' => 2,
            'level' => LogLevel::CRITICAL,
            'context' => [$exception],
        ];

        $formatter = new Simple();
        $output = $formatter->format($event);

        //$this->assertContains($message, $output);
        $this->assertStringContainsString($message, $output);
    }

    public function testAllowsSpecifyingFormatAsConstructorArgument()
    {
        $format = '[%timestamp%] %message%';
        $formatter = new Simple($format);
        $this->assertEquals($format, $formatter->format([]));
    }

}
