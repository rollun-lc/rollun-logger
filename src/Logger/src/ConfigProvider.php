<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger;

use Psr\Log\LoggerInterface;
use rollun\logger\Formatter\ContextToString;
use rollun\logger\Formatter\FluentdFormatter;
use rollun\logger\Formatter\LogStashUdpFormatter;
use rollun\logger\Processor\ExceptionBacktrace;
use rollun\logger\Processor\Factory\LifeCycleTokenReferenceInjectorFactory;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Processor\LifeCycleTokenInjector;
use rollun\logger\Writer\Udp;
use Zend\Log\LoggerAbstractServiceFactory;
use Zend\Log\LoggerServiceFactory;
use Zend\Log\FilterPluginManagerFactory;
use Zend\Log\FormatterPluginManagerFactory;
use Zend\Log\ProcessorPluginManagerFactory;
use Zend\Log\Writer\Stream;
use Zend\Log\WriterPluginManagerFactory;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\InvokableFactory;

class ConfigProvider
{
    /**
     * Return default logger config
     */
    public function __invoke()
    {
        return [
            "dependencies" => $this->getDependencies(),
            "log" => $this->getLog(),
            'log_processors' => $this->getLogProcessors(),
            'log_formatters' => $this->getLogFormatters(),
        ];
    }

    protected function getLogProcessors()
    {
        return [
            'factories' => [
                LifeCycleTokenInjector::class => LifeCycleTokenReferenceInjectorFactory::class,
                IdMaker::class => InvokableFactory::class
            ],
        ];
    }

    protected function getLogFormatters()
    {
        return [
            'factories' => [
                ContextToString::class => InvokableFactory::class,
                FluentdFormatter::class => InvokableFactory::class,
            ],
        ];
    }

    /**
     * Return dependencies config
     * @return array
     */
    public function getDependencies()
    {
        return [
            'abstract_factories' => [
                LoggerAbstractServiceFactory::class,
            ],
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
                'LogFilterManager' => FilterPluginManagerFactory::class,
                'LogFormatterManager' => FormatterPluginManagerFactory::class,
                'LogProcessorManager' => ProcessorPluginManagerFactory::class,
                'LogWriterManager' => WriterPluginManagerFactory::class,
            ],
            'aliases' => [],
        ];
    }

    /**
     * Return default config for logger.
     * @return array
     */
    public function getLog()
    {
        return [
            LoggerInterface::class => [
                'writers' => [
                    [
                        'name' => Stream::class,
                        'options' => [
                            'stream' => 'php://stdout',
                            'formatter' => FluentdFormatter::class
                        ],
                    ],
                    [
                        'name' => Udp::class,

                        'options' => [
                            'client' => [
                                'host' => getenv('LOGSTASH_HOST'),
                                'port' => getenv('LOGSTASH_PORT'),
                            ],
                            'formatter' => new LogStashUdpFormatter(
                                getenv("LOGSTASH_INDEX"),
                                [
                                    'timestamp' => 'timestamp',
                                    'message' => 'message',
                                    'level' => 'level',
                                    'priority' => 'priority',
                                    'context' => 'context',
                                    'lifecycle_token' => 'lifecycle_token',
                                    'parent_lifecycle_token' => 'parent_lifecycle_token',
                                    '_index_name' => '_index_name'
                                ]
                            ),
                            'filters' => [
                                [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '<',
                                        'priority' => 4,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name'    => \rollun\logger\Writer\HttpAsyncMetric::class,
                        'options' => [
                            'url'       => getenv('METRIC_URL'),
                            'filters'   => [
                                [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => 4,
                                    ],
                                ],
                                [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<=',
                                        'priority' => 5,
                                    ],
                                ],
                            ],
                            'formatter' => \rollun\logger\Formatter\Metric::class,
                        ],
                    ],
                ],
                'processors' => [
                    [
                        'name' => IdMaker::class,
                    ],
                    [
                        'name' => ExceptionBacktrace::class
                    ],
                    [
                        'name' => LifeCycleTokenInjector::class,
                    ],
                ],
            ],
        ];
    }
}
