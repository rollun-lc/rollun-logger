<?php
/**
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use rollun\logger\FilterPluginManager;
use rollun\logger\FilterPluginManagerFactory;
use rollun\logger\Filter\FilterInterface;

class FilterPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new FilterPluginManagerFactory();

        $filters = $factory($container, FilterPluginManagerFactory::class);
        $this->assertInstanceOf(FilterPluginManager::class, $filters);

        if (method_exists($filters, 'configure')) {
            // zend-servicemanager v3
            $reflectionProperty = new \ReflectionProperty($filters, 'creationContext');
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($filters);
            $this->assertEquals($value, $container);
            //$this->assertAttributeSame($container, 'creationContext', $filters);
        } else {
            // zend-servicemanager v2
            $this->assertSame($container, $filters->getServiceLocator());
        }
    }

    /**
     * @depends testFactoryReturnsPluginManager
     */
    public function testFactoryConfiguresPluginManagerUnderContainer()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $filter = $this->prophesize(FilterInterface::class)->reveal();

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container, FilterPluginManagerFactory::class, [
            'services' => [
                'test' => $filter,
            ],
        ]);
        $this->assertSame($filter, $filters->get('test'));
    }

    public function testConfiguresFilterServicesWhenFound()
    {
        $filter = $this->createMock(FilterInterface::class);
        $config = [
            'log_filters' => [
                'aliases' => [
                    'test' => 'test-too',
                ],
                'factories' => [
                    'test-too' => function ($container) use ($filter) {
                        return $filter;
                    },
                ],
            ],
        ];

        $container = new SimpleArrayContainer(['config' => $config]);

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container, 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
        $this->assertTrue($filters->has('test'));
        $this->assertSame($filter, $filters->get('test'));
        $this->assertTrue($filters->has('test-too'));
        $this->assertSame($filter, $filters->get('test-too'));
    }

    public function testDoesNotConfigureFilterServicesWhenServiceListenerPresent()
    {
        $container = new SimpleArrayContainer(['ServiceListener' => 'ServiceListener']);

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container, 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
        $this->assertFalse($filters->has('test'));
        $this->assertFalse($filters->has('test-too'));
    }

    public function testDoesNotConfigureFilterServicesWhenConfigServiceNotPresent()
    {
        $container = new SimpleArrayContainer([]);

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container, 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
    }

    public function testDoesNotConfigureFilterServicesWhenConfigServiceDoesNotContainFiltersConfig()
    {
        $container = new SimpleArrayContainer(['config' => ['foo' => 'bar']]);

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container, 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
        $this->assertFalse($filters->has('foo'));
    }
}
