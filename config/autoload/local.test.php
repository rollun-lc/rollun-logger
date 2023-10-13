<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use rollun\logger\FilterPluginManagerFactory;
use rollun\logger\Formatter\ContextToString;
use rollun\logger\Formatter\LogStashFormatter;
use rollun\logger\FormatterPluginManagerFactory;
use rollun\logger\Logger;
use rollun\logger\LoggerAbstractServiceFactory;
use rollun\logger\LoggerServiceFactory;
use rollun\logger\Processor\ChangeLevel;
use rollun\logger\Processor\CountPerTime;
use rollun\logger\Processor\Factory\ConditionalProcessorAbstractFactory;
use rollun\logger\Processor\Factory\CountPerTimeFactory;
use rollun\logger\Processor\Factory\ProcessorAbstractFactory;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Processor\ProcessorWithCount;
use rollun\logger\ProcessorPluginManagerFactory;
use rollun\logger\Writer\Db as WriterDb;
use rollun\logger\Writer\Elasticsearch;
use rollun\logger\Writer\Mock as WriterMock;
use rollun\logger\Writer\Udp;
use rollun\logger\WriterPluginManagerFactory;
use Zend\Db\Adapter\AdapterInterface;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
	'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'Pdo_Mysql',
		'database' => getenv('DB_NAME'),
		'username' => getenv('DB_USER'),
		'password' => getenv('DB_PASS'),
        'hostname' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT') ?: 3306,
	],
	'log_formatters' => [
		'factories' => [
			ContextToString::class => InvokableFactory::class,
		],
	],
	'log_processors' => [
        'abstract_factories' => [
            ProcessorAbstractFactory::class,
            ConditionalProcessorAbstractFactory::class,
        ],
		'factories' => [
            CountPerTime::class => CountPerTimeFactory::class,
			IdMaker::class => InvokableFactory::class,
		],
	],
	'dependencies' => [
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
		'aliases' => [
			'logDbAdapter' => AdapterInterface::class,
		],
	],
    ConditionalProcessorAbstractFactory::KEY => [
        'DuplicateAmazonProductsCrossMatch' => [
            ConditionalProcessorAbstractFactory::KEY_FILTERS => [
                [
                    'name'    => 'regex',
                    'options' => [
                        'regex' => '/^TEST$/'
                    ],
                ],
            ],
            ConditionalProcessorAbstractFactory::KEY_PROCESSORS => [
                [
                    'name' => CountPerTime::class,
                    'options' => [
                        'time' => 60,
                        'count' => 2,
                        'onTrue' => [
                            [
                                'name' => 'ChangeErrorToWarning',
                            ],
                        ],
                        'onFalse' => [
                            [
                                'name' => ProcessorWithCount::class,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    ProcessorAbstractFactory::KEY => [
        'ChangeErrorToWarning' => [
            'name' => ChangeLevel::class,
            'options' => [
                'from' => LogLevel::ERROR,
                'to' => LogLevel::WARNING,
            ],
        ],
    ],
	'log' => [
		LoggerInterface::class => [
			'processors' => [
				[
					'name' => IdMaker::class,
				],
                [
                    'name' => 'DuplicateAmazonProductsCrossMatch',
                ]
			],
			'writers' => [
				[
					'name' => WriterMock::class,
				],
                'udp_logstash' => [
                    'name' => Udp::class,

                    'options' => [
                        'formatter' => LogStashFormatter::class
                    ],
                ],
			],
		],
		'loggerWithElasticsearch' => [
			'writers' => [
				[
                    'name' => Elasticsearch::class,
                    'options' => [
                        'client' => [
                            'hosts' => ['http://localhost:9200']
                        ],
                        'indexName' => 'my_index'
                    ],
				],
			],
		],
		'logWithDbWriter' => [
			'processors' => [
				[
					'name' => IdMaker::class,
				],
			],
			'writers' => [
				[
					'name' => WriterDb::class,
					'options' => [
						'db' => 'logDbAdapter',
						'table' => 'logs',
						'column' => [
							'id' => 'id',
							'timestamp' => 'timestamp',
							'message' => 'message',
							'level' => 'level',
							'priority' => 'priority',
							'context' => 'context',
							'lifecycle_token' => 'lifecycle_token',
						],
						'formatter' => ContextToString::class,
					],
				],
			],
		],
	],
];
