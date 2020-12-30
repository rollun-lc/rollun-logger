<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger;

use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\Mock;
use rollun\logger\WriterPluginManager;
use Zend\ServiceManager\ServiceManager;

class WriterPluginManagerTest extends TestCase
{
    /**
     * @var WriterPluginManager
     */
    protected $plugins;

    public function setUp()
    {
        $this->plugins = new WriterPluginManager(new ServiceManager());
    }

    public function testInvokableClassFirephp()
    {
        $mock = $this->plugins->get('mock');
        $this->assertInstanceOf(Mock::class, $mock);
    }
}
