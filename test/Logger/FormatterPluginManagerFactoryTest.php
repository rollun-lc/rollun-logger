<?php

/**
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Rollun\Test\Logger;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use rollun\logger\Formatter\FormatterInterface;
use rollun\logger\FormatterPluginManager;
use rollun\logger\FormatterPluginManagerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FormatterPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager()
    {
        $container = new SimpleArrayContainer([]);
        $factory = new FormatterPluginManagerFactory();

        $formatters = $factory($container, FormatterPluginManagerFactory::class);
        $this->assertInstanceOf(FormatterPluginManager::class, $formatters);

        $reflectionProperty = new \ReflectionProperty($formatters, 'creationContext');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($formatters);
        $this->assertEquals($value, $container);
    }

    /**
     * @depends testFactoryReturnsPluginManager
     */
    public function testFactoryConfiguresPluginManagerUnderContainer()
    {
        $container = new SimpleArrayContainer([]);
        $formatter = $this->createMock(FormatterInterface::class);

        $factory = new FormatterPluginManagerFactory();
        $formatters = $factory($container, FormatterPluginManagerFactory::class, [
            'services' => [
                'test' => $formatter,
            ],
        ]);
        $this->assertSame($formatter, $formatters->get('test'));
    }

    public function testConfiguresFormatterServicesWhenFound()
    {
        $formatter = $this->createMock(FormatterInterface::class);
        $config = [
            'log_formatters' => [
                'aliases' => [
                    'test' => 'test-too',
                ],
                'factories' => [
                    'test-too' => fn($container) => $formatter,
                ],
            ],
        ];

        $container = new SimpleArrayContainer(['config' => $config]);

        $factory = new FormatterPluginManagerFactory();
        $formatters = $factory($container, 'FormatterManager');

        $this->assertInstanceOf(FormatterPluginManager::class, $formatters);
        $this->assertTrue($formatters->has('test'));
        $this->assertSame($formatter, $formatters->get('test'));
        $this->assertTrue($formatters->has('test-too'));
        $this->assertSame($formatter, $formatters->get('test-too'));
    }

    public function testDoesNotConfigureFormatterServicesWhenServiceListenerPresent()
    {
        $container = new SimpleArrayContainer(['ServiceListener' => 'ServiceListener']);

        $factory = new FormatterPluginManagerFactory();
        $formatters = $factory($container, 'FormatterManager');

        $this->assertInstanceOf(FormatterPluginManager::class, $formatters);
        $this->assertFalse($formatters->has('test'));
        $this->assertFalse($formatters->has('test-too'));
    }

    public function testDoesNotConfigureFormatterServicesWhenConfigServiceNotPresent()
    {
        $container = new SimpleArrayContainer([]);
        $factory = new FormatterPluginManagerFactory();
        $formatters = $factory($container, 'FormatterManager');

        $this->assertInstanceOf(FormatterPluginManager::class, $formatters);
    }

    public function testDoesNotConfigureFormatterServicesWhenConfigServiceDoesNotContainFormattersConfig()
    {
        $container = new SimpleArrayContainer(['config' => ['foo' => 'bar']]);

        $factory = new FormatterPluginManagerFactory();
        $formatters = $factory($container, 'FormatterManager');

        $this->assertInstanceOf(FormatterPluginManager::class, $formatters);
        $this->assertFalse($formatters->has('foo'));
    }
}
