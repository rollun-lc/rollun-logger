<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Rollun\Test\Logger;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use rollun\logger\Logger;
use rollun\logger\LoggerAwareTrait;

class LoggerAwareTraitTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testSetLogger()
    {
        $object = $this->getObjectForTrait(LoggerAwareTrait::class);

        //$this->assertAttributeEquals(null, 'logger', $object);
        $reflectionProperty = new \ReflectionProperty($object, 'logger');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);
        $this->assertEquals($value, null);

        $logger = new Logger;

        $object->setLogger($logger);

        //$this->assertAttributeEquals($logger, 'logger', $object);
        $reflectionProperty = new \ReflectionProperty($object, 'logger');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);
        $this->assertEquals($value, $logger);
    }
}
