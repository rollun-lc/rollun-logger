<?php

namespace Rollun\Test\Logger\Processor;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use rollun\logger\Processor\ExceptionBacktrace;

/**
 * Class ExceptionBacktraceTest
 * @package Rollun\Test\Logger\Processor
 */
class ExceptionBacktraceTest extends TestCase
{
    public function testPositive()
    {
        $stackSize = 5;
        $processor = new ExceptionBacktrace();
        $event['context']['exception'] = $this->getTestedException($stackSize);
        $event = $processor->process($event);

        foreach ($event['context']['backtrace'] as $stack) {
            $this->assertEquals($stack['code'], $stackSize);
            $this->assertEquals($stack['message'], "message {$stackSize}");
            $stackSize--;
        }
    }

    public function testNegative()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Exception argument must implement \Throwable interface, ' . get_class($this) . ' given'
        );
        $processor = new ExceptionBacktrace();
        $event['context']['exception'] = $this;
        $processor->process($event);
    }

    /**
     * @param int $stack
     * @return \Exception
     */
    public function getTestedException(int $stack)
    {
        if (!($stack - 1)) {
            return new \Exception("message {$stack}", $stack, null);
        }

        try {
            throw $this->getTestedException(--$stack);
        } catch (\Throwable $e) {
            ++$stack;
            return new \Exception("message {$stack}", $stack, $e);
        }
    }
}
