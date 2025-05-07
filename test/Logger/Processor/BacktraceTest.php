<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Rollun\Test\Logger\Processor;

use PHPUnit\Framework\TestCase;
use rollun\logger\Processor\Backtrace;
use Psr\Log\LogLevel;

class BacktraceTest extends TestCase
{
    public function testProcess()
    {
        $processor = new Backtrace();

        $event = [
            'timestamp' => '',
            'priority' => 1,
            'level' => LogLevel::CRITICAL,
            'message' => 'foo',
            'context' => [],
        ];

        $event = $processor->process($event);

        $this->assertArrayHasKey('file', $event['context']);
        $this->assertArrayHasKey('line', $event['context']);
        $this->assertArrayHasKey('class', $event['context']);
        $this->assertArrayHasKey('function', $event['context']);
    }

}
