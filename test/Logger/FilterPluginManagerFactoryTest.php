<?php
/**
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use PHPUnit\Framework\TestCase;
use rollun\logger\FilterPluginManager;
use rollun\logger\FilterPluginManagerFactory;
use rollun\logger\Filter\FilterInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FilterPluginManagerFactoryTest extends TestCase
{
    /**
     * @throws ContainerException
     */
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
     * @throws ContainerException
     */
    public function testFactoryConfiguresPluginManagerUnderContainerInterop()
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

    /**
     * @depends testFactoryReturnsPluginManager
     */
    public function testFactoryConfiguresPluginManagerUnderServiceManagerV2()
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $filter = $this->prophesize(FilterInterface::class)->reveal();

        $factory = new FilterPluginManagerFactory();
        $factory->setCreationOptions([
            'services' => [
                'test' => $filter,
            ],
        ]);

        $filters = $factory->createService($container->reveal());
        $this->assertSame($filter, $filters->get('test'));
    }

    /**
     * @throws ContainerException
     */
    public function testConfiguresFilterServicesWhenFound()
    {
        $filter = $this->prophesize(FilterInterface::class)->reveal();
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

        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(false);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container->reveal(), 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
        $this->assertTrue($filters->has('test'));
        $this->assertSame($filter, $filters->get('test'));
        $this->assertTrue($filters->has('test-too'));
        $this->assertSame($filter, $filters->get('test-too'));
    }

    /**
     * @throws ContainerException
     */
    public function testDoesNotConfigureFilterServicesWhenServiceListenerPresent()
    {
        $filter = $this->prophesize(FilterInterface::class)->reveal();
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

        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(true);
        $container->has('config')->shouldNotBeCalled();
        $container->get('config')->shouldNotBeCalled();

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container->reveal(), 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
        $this->assertFalse($filters->has('test'));
        $this->assertFalse($filters->has('test-too'));
    }

    /**
     * @throws ContainerException
     */
    public function testDoesNotConfigureFilterServicesWhenConfigServiceNotPresent()
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(false);
        $container->has('config')->willReturn(false);
        $container->get('config')->shouldNotBeCalled();

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container->reveal(), 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
    }

    /**
     * @throws ContainerException
     */
    public function testDoesNotConfigureFilterServicesWhenConfigServiceDoesNotContainFiltersConfig()
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(false);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn(['foo' => 'bar']);

        $factory = new FilterPluginManagerFactory();
        $filters = $factory($container->reveal(), 'FilterManager');

        $this->assertInstanceOf(FilterPluginManager::class, $filters);
        $this->assertFalse($filters->has('foo'));
    }
}
