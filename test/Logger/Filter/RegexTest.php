<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Rollun\Test\Logger\Filter;

use PHPUnit\Framework\TestCase;
use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Filter\Regex;

/**
 * @group      Zend_Log
 */
class RegexTest extends TestCase
{
    public function testMessageFilterRecognizesInvalidRegularExpression()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid reg');
        new Regex('invalid regexp');
    }

    public function testMessageFilter()
    {
        $filter = new Regex('/accept/');
        $this->assertTrue($filter->filter(['message' => 'foo accept bar']));
        $this->assertFalse($filter->filter(['message' => 'foo reject bar']));
    }
}
