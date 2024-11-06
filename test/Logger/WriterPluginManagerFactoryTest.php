<?php
/**
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\test\logger;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\WriterInterface;
use rollun\logger\WriterPluginManager;
use rollun\logger\WriterPluginManagerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;

class WriterPluginManagerFactoryTest extends TestCase
{
    public function testFactoryReturnsPluginManager()
    {
        $container = new SimpleArrayContainer([]);
        $factory = new WriterPluginManagerFactory();

        $writers = $factory($container, WriterPluginManagerFactory::class);
        $this->assertInstanceOf(WriterPluginManager::class, $writers);

        $property = new \ReflectionProperty($writers, 'creationContext');
        $property->setAccessible(true);
        $creationContext = $property->getValue($writers);
        $this->assertSame($container, $creationContext);
    }

    /**
     * @depends testFactoryReturnsPluginManager
     */
    public function testFactoryConfiguresPluginManagerUnderContainer()
    {
        $container = new SimpleArrayContainer([]);
        $writer = $this->createMock(WriterInterface::class);

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container, WriterPluginManagerFactory::class, [
            'services' => [
                'test' => $writer,
            ],
        ]);
        $this->assertSame($writer, $writers->get('test'));
    }

    public function testConfiguresWriterServicesWhenFound()
    {
        $writer = $this->prophesize(WriterInterface::class)->reveal();
        $config = [
            'log_writers' => [
                'aliases' => [
                    'test' => 'test-too',
                ],
                'factories' => [
                    'test-too' => function ($container) use ($writer) {
                        return $writer;
                    },
                ],
            ],
        ];

        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(false);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container->reveal(), 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
        $this->assertTrue($writers->has('test'));
        $this->assertSame($writer, $writers->get('test'));
        $this->assertTrue($writers->has('test-too'));
        $this->assertSame($writer, $writers->get('test-too'));
    }

    public function testDoesNotConfigureWriterServicesWhenServiceListenerPresent()
    {
        $writer = $this->prophesize(WriterInterface::class)->reveal();
        $config = [
            'log_writers' => [
                'aliases' => [
                    'test' => 'test-too',
                ],
                'factories' => [
                    'test-too' => function ($container) use ($writer) {
                        return $writer;
                    },
                ],
            ],
        ];

        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(true);
        $container->has('config')->shouldNotBeCalled();
        $container->get('config')->shouldNotBeCalled();

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container->reveal(), 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
        $this->assertFalse($writers->has('test'));
        $this->assertFalse($writers->has('test-too'));
    }

    public function testDoesNotConfigureWriterServicesWhenConfigServiceNotPresent()
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(false);
        $container->has('config')->willReturn(false);
        $container->get('config')->shouldNotBeCalled();

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container->reveal(), 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
    }

    public function testDoesNotConfigureWriterServicesWhenConfigServiceDoesNotContainWritersConfig()
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $container->has('ServiceListener')->willReturn(false);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn(['foo' => 'bar']);

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container->reveal(), 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
        $this->assertFalse($writers->has('foo'));
    }
}
