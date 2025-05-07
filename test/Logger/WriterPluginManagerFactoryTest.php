<?php

/**
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Rollun\Test\Logger;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use rollun\logger\Writer\WriterInterface;
use rollun\logger\WriterPluginManager;
use rollun\logger\WriterPluginManagerFactory;

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
        $writer = $this->createMock(WriterInterface::class);
        $config = [
            'log_writers' => [
                'aliases' => [
                    'test' => 'test-too',
                ],
                'factories' => [
                    'test-too' => fn($container) => $writer,
                ],
            ],
        ];

        $container = new SimpleArrayContainer(['config' => $config]);

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container, 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
        $this->assertTrue($writers->has('test'));
        $this->assertSame($writer, $writers->get('test'));
        $this->assertTrue($writers->has('test-too'));
        $this->assertSame($writer, $writers->get('test-too'));
    }

    public function testDoesNotConfigureWriterServicesWhenServiceListenerPresent()
    {
        $container = new SimpleArrayContainer(['ServiceListener' => 'ServiceListener']);

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container, 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
        $this->assertFalse($writers->has('test'));
        $this->assertFalse($writers->has('test-too'));
    }

    public function testDoesNotConfigureWriterServicesWhenConfigServiceNotPresent()
    {
        $container = new SimpleArrayContainer([]);

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container, 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
    }

    public function testDoesNotConfigureWriterServicesWhenConfigServiceDoesNotContainWritersConfig()
    {
        $container = new SimpleArrayContainer(['config' => ['foo' => 'bar']]);

        $factory = new WriterPluginManagerFactory();
        $writers = $factory($container, 'WriterManager');

        $this->assertInstanceOf(WriterPluginManager::class, $writers);
        $this->assertFalse($writers->has('foo'));
    }
}
