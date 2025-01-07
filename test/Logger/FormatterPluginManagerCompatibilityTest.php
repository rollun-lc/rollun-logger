<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger;

use PHPUnit\Framework\TestCase;
use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Formatter\FormatterInterface;
use rollun\logger\FormatterPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;

class FormatterPluginManagerCompatibilityTest extends TestCase
{
    use CommonPluginManagerTrait;
    use ServicesNotSharedByDefaultTrait;

    protected static function getPluginManager(): FormatterPluginManager
    {
        return new FormatterPluginManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return InvalidArgumentException::class;
    }

    protected function getInstanceOf()
    {
        return FormatterInterface::class;
    }
}
