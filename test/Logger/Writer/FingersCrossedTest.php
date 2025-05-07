<?php
/**
 * Zend Framework (http://framework.zend.com/)
*
* @link      http://github.com/zendframework/zf2 for the canonical source repository
* @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
* @license   http://framework.zend.com/license/new-bsd New BSD License
*/

namespace Rollun\Test\Logger\Writer;

use PHPUnit\Framework\TestCase;
use rollun\logger\Filter\Priority;
use rollun\logger\Formatter\FormatterInterface;
use rollun\logger\Writer\FingersCrossed as FingersCrossedWriter;
use rollun\logger\Writer\Mock;
use rollun\logger\Writer\Mock as MockWriter;

class FingersCrossedTest extends TestCase
{
    public function testBuffering()
    {
        $wrappedWriter = new MockWriter();
        $writer = new FingersCrossedWriter($wrappedWriter, 2);

        $writer->write(['priority' => 3, 'message' => 'foo']);

        $this->assertSame(count($wrappedWriter->events), 0);
    }

    public function testFlushing()
    {
        $wrappedWriter = new MockWriter();
        $writer = new FingersCrossedWriter($wrappedWriter, 2);

        $writer->write(['priority' => 3, 'message' => 'foo']);
        $writer->write(['priority' => 1, 'message' => 'bar']);

        $this->assertSame(count($wrappedWriter->events), 2);
    }

    public function testAfterFlushing()
    {
        $wrappedWriter = new MockWriter();
        $writer = new FingersCrossedWriter($wrappedWriter, 2);

        $writer->write(['priority' => 3, 'message' => 'foo']);
        $writer->write(['priority' => 1, 'message' => 'bar']);
        $writer->write(['priority' => 3, 'message' => 'bar']);

        $this->assertSame(count($wrappedWriter->events), 3);
    }

    public function setWriterByName()
    {
        $writer = new FingersCrossedWriter('mock');
        $this->assertAttributeInstanceOf(Mock::class, 'writer', $writer);
    }

    public function testConstructorOptions()
    {
        $options = ['writer' => 'mock', 'priority' => 3];
        $writer = new FingersCrossedWriter($options);

        //$this->assertAttributeInstanceOf(Mock::class, 'writer', $writer);
        $property = new \ReflectionProperty($writer, 'writer');
        $property->setAccessible(true);
        $instance = $property->getValue($writer);
        $this->assertInstanceOf(MockWriter::class, $instance);

        //$filters = $this->readAttribute($writer, 'filters');
        $property = new \ReflectionProperty($writer, 'filters');
        $property->setAccessible(true);
        $filters = $property->getValue($writer);

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(Priority::class, $filters[0]);

        //$this->assertAttributeEquals(3, 'priority', $filters[0]);
        $property = new \ReflectionProperty($filters[0], 'priority');
        $property->setAccessible(true);
        $priority = $property->getValue($filters[0]);
        $this->assertEquals(3, $priority);
    }

    public function testFormattingIsNotSupported()
    {
        $options = ['writer' => 'mock', 'priority' => 3];
        $writer = new FingersCrossedWriter($options);

        $writer->setFormatter($this->createMock(FormatterInterface::class));

        //$this->assertAttributeEmpty('formatter', $writer);
        $property = new \ReflectionProperty($writer, 'formatter');
        $property->setAccessible(true);
        $formatter = $property->getValue($writer);
        $this->assertNull($formatter);
    }
}
