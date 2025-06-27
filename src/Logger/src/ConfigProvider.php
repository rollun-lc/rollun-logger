<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\logger;

use Psr\Log\LoggerInterface;
use rollun\logger\Factory\RecursiveJsonTruncatorFactory;
use rollun\logger\Factory\LifeCycleTokenFactory;
use rollun\logger\Factory\RedisStorageFactory;
use rollun\logger\Formatter\ContextToString;
use rollun\logger\Formatter\Factory\LogStashFormatterFactory;
use rollun\logger\Formatter\FluentdFormatter;
use rollun\logger\Formatter\LogStashFormatter;
use rollun\logger\Formatter\SlackFormatter;
use rollun\logger\Middleware\Factory\RequestLoggedMiddlewareFactory;
use rollun\logger\Middleware\RequestLoggedMiddleware;
use rollun\logger\Processor\CountPerTime;
use rollun\logger\Processor\ExceptionBacktrace;
use rollun\logger\Processor\Factory\CountPerTimeFactory;
use rollun\logger\Processor\Factory\LifeCycleTokenReferenceInjectorFactory;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Processor\LifeCycleTokenInjector;
use rollun\logger\Processor\ProcessorWithCount;
use rollun\logger\Prometheus\Collector;
use rollun\logger\Prometheus\PushGateway;
use rollun\logger\Services\RecursiveJsonTruncator;
use rollun\logger\Writer\Factory\PrometheusFactory;
use rollun\logger\Writer\Factory\WriterFactory;
use rollun\logger\Writer\PrometheusWriter;
use rollun\logger\Writer\Slack;
use rollun\logger\Writer\Stream;
use rollun\logger\Writer\Tcp;
use rollun\logger\Writer\Udp;
use rollun\logger\Writer\HttpAsyncMetric;
use rollun\logger\Formatter\Metric;
use Laminas\ServiceManager\Factory\InvokableFactory;

class ConfigProvider
{
    /**
     * Return default logger config
     */
    public function __invoke()
    {
        return [
            "dependencies"   => $this->getDependencies(),
            "log"            => $this->getLog(),
            'log_processors' => $this->getLogProcessors(),
            'log_formatters' => $this->getLogFormatters(),
            'log_writers'    => $this->getLogWriters(),
        ];
    }

    protected function getLogProcessors()
    {
        return [
            'invokables' => [
                IdMaker::class => IdMaker::class,
                ProcessorWithCount::class => ProcessorWithCount::class,
            ],
            'factories' => [
                LifeCycleTokenInjector::class => LifeCycleTokenReferenceInjectorFactory::class,
                CountPerTime::class => CountPerTimeFactory::class,
            ],
        ];
    }

    protected function getLogFormatters()
    {
        return [
            'factories' => [
                ContextToString::class  => InvokableFactory::class,
                FluentdFormatter::class => InvokableFactory::class,
                LogStashFormatter::class => LogStashFormatterFactory::class,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getLogWriters()
    {
        return [
            'factories' => [
                PrometheusWriter::class => PrometheusFactory::class,
                Udp::class => WriterFactory::class,
                Tcp::class => WriterFactory::class,
            ],
        ];
    }

    /**
     * Return dependencies config
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'abstract_factories' => [
                LoggerAbstractServiceFactory::class,
            ],
            'factories'          => [
                Logger::class         => LoggerServiceFactory::class,
                'LogFilterManager'    => FilterPluginManagerFactory::class,
                'LogFormatterManager' => FormatterPluginManagerFactory::class,
                'LogProcessorManager' => ProcessorPluginManagerFactory::class,
                'LogWriterManager'    => WriterPluginManagerFactory::class,
                'StorageForLogsCount' => RedisStorageFactory::class,
                RecursiveJsonTruncator::class => RecursiveJsonTruncatorFactory::class,
                'RecursiveJsonTruncatorDefaultParams' => function () {
                    return [
                        'limit'         => 1000,
                        'depthLimit'    => 3,
                        'maxArrayChars' => 1000,
                        'arrayLimit'    => 3,
                    ];
                },

                //LifeCycleToken
                LifeCycleToken::class => LifeCycleTokenFactory::class,

                // Middlewares
                RequestLoggedMiddleware::class => RequestLoggedMiddlewareFactory::class,
            ],
            'invokables'         => [
                PushGateway::class => PushGateway::class,
                Collector::class   => Collector::class,
            ],
            'aliases'            => [],
        ];
    }

    /**
     * Return default config for logger.
     *
     * @return array
     */
    public function getLog()
    {
        return [
            LoggerInterface::class => [
                'writers'    => [
                    'stream_stdout' => [
                        'name'    => Stream::class,
                        'options' => [
                            'stream'    => 'php://stdout',
                            'formatter' => new FluentdFormatter(),
                        ],
                    ],
                    'udp_logstash' => [
                        'name' => Udp::class,

                        'options' => [
                            'client'    => [
                                'host' => getenv('LOGSTASH_HOST'),
                                'port' => getenv('LOGSTASH_PORT'),
                                'options' => [
                                    'timeout' => 10,
                                    'writingTimeout' => 10,
                                    'connectionTimeout' => 10,
                                ],
                            ],
                            'formatter' => LogStashFormatter::class,
                            'filters'   => [
                                'priority_<_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<',
                                        'priority' => 4,
                                    ],
                                ],
                                'regex_not_metrics' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^((?!METRICS).)*$/',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'http_async_metric_metrics' => [
                        'name'    => HttpAsyncMetric::class,
                        'options' => [
                            'url'       => getenv('METRIC_URL'),
                            'filters'   => [
                                'priority_>=_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => 4, // we should send only warnings or notices
                                    ],
                                ],
                                'priority_<=_5' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<=',
                                        'priority' => 5, // we should send only warnings or notices
                                    ],
                                ],
                                'regex_only_metrics' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^METRICS$/',
                                    ],
                                ],
                            ],
                            'formatter' => Metric::class,
                        ],
                    ],
                    'prometheus_metrics_gauge' => [
                        PrometheusFactory::COLLECTOR => Collector::class, // не обязательный параметр.
                        PrometheusFactory::JOB_NAME  => 'logger_job',  // не обязательный параметр.
                        'name'                       => PrometheusWriter::class,
                        'options'                    => [
                            PrometheusFactory::TYPE => PrometheusFactory::TYPE_GAUGE,
                            'filters'               => [
                                'regex_only_metrics_gauge' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^METRICS_GAUGE$/',
                                    ],
                                ],
                                'priority_>=_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => 4, // we should send only warnings or notices
                                    ],
                                ],
                                'priority_<=_5' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<=',
                                        'priority' => 5, // we should send only warnings or notices
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'prometheus_metrics_counter' => [
                        'name'    => PrometheusWriter::class,
                        'options' => [
                            PrometheusFactory::TYPE => PrometheusFactory::TYPE_COUNTER,
                            'filters'               => [
                                'regex_only_metrics_counter' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^METRICS_COUNTER$/',
                                    ],
                                ],
                                'priority_>=_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => 4, // we should send only warnings or notices
                                    ],
                                ],
                                'priority_<=_5' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<=',
                                        'priority' => 5, // we should send only warnings or notices
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'slack' => [
                        'name'    => Slack::class,
                        'options' => [
                            'token'     => getenv('SLACK_TOKEN'),
                            'channel'   => getenv('SLACK_CHANNEL'),
                            'filters'   => [
                                'regex_not_metrics' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^((?!METRICS).)*$/',
                                    ],
                                ],
                                'priority_<_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<',
                                        'priority' => 4,
                                    ],
                                ],
                            ],
                            'formatter' => SlackFormatter::class,
                        ],
                    ],
                ],
                'processors' => [
                    [
                        'name' => IdMaker::class,
                    ],
                    [
                        'name' => ExceptionBacktrace::class,
                    ],
                    [
                        'name' => LifeCycleTokenInjector::class,
                    ],
                ],
            ],
        ];
    }
}
