<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use Psr\Log\LoggerInterface;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Writer\Elasticsearch;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Log\Writer\Mock as WriterMock;
use Zend\Log\LoggerAbstractServiceFactory;
use Zend\Log\LoggerServiceFactory;
use Zend\Log\FilterPluginManagerFactory;
use Zend\Log\FormatterPluginManagerFactory;
use Zend\Log\ProcessorPluginManagerFactory;
use Zend\Log\WriterPluginManagerFactory;
use Zend\Log\Logger;
use Zend\Log\Writer\Db as WriterDb;
use rollun\logger\Formatter\ContextToString;

return [
	'db' => [
		'driver' => getenv('DB_DRIVER'),
		'database' => getenv('DB_NAME'),
		'username' => getenv('DB_USER'),
		'password' => getenv('DB_PASS'),
		'port' => getenv('DB_PORT'),
	],
	'log_formatters' => [
		'factories' => [
			ContextToString::class => InvokableFactory::class,
		],
	],
	'log_processors' => [
		'factories' => [
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
			//'logDbAdapter' => AdapterInterface::class,
		],
	],
	'log' => [
		LoggerInterface::class => [
			'processors' => [
				[
					'name' => IdMaker::class,
				],
			],
			'writers' => [
				[
					'name' => WriterMock::class,
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
