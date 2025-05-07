<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Rollun\Test\Logger;

use Laminas\Db\Adapter\Adapter;
use PHPUnit\Framework\TestCase;
use rollun\logger\Logger;
use rollun\logger\LoggerAbstractServiceFactory;
use rollun\logger\Processor\ProcessorInterface;
use rollun\logger\ProcessorPluginManager;
use rollun\logger\Writer\WriterInterface;
use rollun\logger\WriterPluginManager;
use rollun\logger\Writer\Noop;
use rollun\logger\Writer\Db as DbWriter;
use rollun\logger\WriterPluginManagerFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;

class LoggerAbstractServiceFactoryTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceManager;

    /**
     * Set up LoggerAbstractServiceFactory and loggers configuration.
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->serviceManager = new ServiceManager();
        $config = new Config([
            'abstract_factories' => [LoggerAbstractServiceFactory::class],
            'services' => [
                'config' => [
                    'log' => [
                        'Application\Frontend' => [],
                        'Application\Backend'  => [],
                    ],
                ],
            ],
        ]);
        $config->configureServiceManager($this->serviceManager);
    }

    /**
     * @return array
     */
    public function providerValidLoggerService()
    {
        return [
            ['Application\Frontend'],
            ['Application\Backend'],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidLoggerService()
    {
        return [
            ['Logger\Application\Unknown'],
            ['Logger\Application\Frontend'],
            ['Application\Backend\Logger'],
        ];
    }

    /**
     * @param string $service
     * @dataProvider providerValidLoggerService
     */
    public function testValidLoggerService(string $service)
    {
        $actual = $this->serviceManager->get($service);
        $this->assertInstanceOf(Logger::class, $actual);
    }

    /**
     * @dataProvider providerInvalidLoggerService
     *
     * @param string $service
     */
    public function testInvalidLoggerService(string $service)
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->serviceManager->get($service);
    }

    /**
     * @group 5254
     */
    public function testRetrievesDatabaseServiceFromServiceManagerWhenEncounteringDbWriter()
    {
        $db = $this->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config = new Config([
            'abstract_factories' => [LoggerAbstractServiceFactory::class],
            'factories' => [
                'LogWriterManager'    => WriterPluginManagerFactory::class,
            ],
            'services' => [
                'Db\Logger' => $db,
                'config' => [
                    'log' => [
                        'Application\Log' => [
                            'writers' => [
                                [
                                    'name'     => 'db',
                                    'priority' => 1,
                                    'options'  => [
                                        'separator' => '_',
                                        'column'    => [],
                                        'table'     => 'applicationlog',
                                        'db'        => 'Db\Logger',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $serviceManager = new ServiceManager();
        $config->configureServiceManager($serviceManager);

        $logger = $serviceManager->get('Application\Log');
        $this->assertInstanceOf(Logger::class, $logger);
        $writers = $logger->getWriters();
        $found   = false;

        foreach ($writers as $writer) {
            if ($writer instanceof DbWriter) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Did not find expected DB writer');
        //$this->assertAttributeSame($db, 'db', $writer);
        $reflectionProperty = new \ReflectionProperty($writer, 'db');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($writer);
        $this->assertEquals($db, $value);
    }

    /**
     * @group 4455
     */
    public function testWillInjectWriterPluginManagerIfAvailable()
    {
        $writers = new WriterPluginManager(new ServiceManager());
        $mockWriter = $this->createMock(WriterInterface::class);
        $writers->setService('CustomWriter', $mockWriter);

        $config = new Config([
            'abstract_factories' => [LoggerAbstractServiceFactory::class],
            'services' => [
                'LogWriterManager' => $writers,
                'config' => [
                    'log' => [
                        'Application\Frontend' => [
                            'writers' => [['name' => 'CustomWriter']],
                        ],
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $log = $services->get('Application\Frontend');
        $logWriters = $log->getWriters();
        $this->assertEquals(1, count($logWriters));
        $writer = $logWriters->current();
        $this->assertSame($mockWriter, $writer);
    }

    /**
     * @group 4455
     */
    public function testWillInjectProcessorPluginManagerIfAvailable()
    {
        $processors = new ProcessorPluginManager(new ServiceManager());
        $mockProcessor = $this->createMock(ProcessorInterface::class);
        $processors->setService('CustomProcessor', $mockProcessor);

        $config = new Config([
            'abstract_factories' => [LoggerAbstractServiceFactory::class],
            'services' => [
                'LogProcessorManager' => $processors,
                'config' => [
                    'log' => [
                        'Application\Frontend' => [
                            'writers'    => [['name' => Noop::class]],
                            'processors' => [['name' => 'CustomProcessor']],
                        ],
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $log = $services->get('Application\Frontend');
        $logProcessors = $log->getProcessors();
        $this->assertEquals(1, count($logProcessors));
        $processor = $logProcessors->current();
        $this->assertSame($mockProcessor, $processor);
    }
}
