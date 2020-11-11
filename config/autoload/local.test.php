<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use Psr\Log\LoggerInterface;
use rollun\logger\FilterPluginManagerFactory;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Writer\Elasticsearch;
use Zend\Db\Adapter\AdapterInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use rollun\logger\Writer\Mock as WriterMock;
use rollun\logger\LoggerAbstractServiceFactory;
use rollun\logger\LoggerServiceFactory;
use rollun\logger\FormatterPluginManagerFactory;
use rollun\logger\ProcessorPluginManagerFactory;
use rollun\logger\WriterPluginManagerFactory;
use rollun\logger\Logger;
use rollun\logger\Writer\Db as WriterDb;
use rollun\logger\Formatter\ContextToString;

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
			'logDbAdapter' => AdapterInterface::class,
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
						'client' => [[
							'host' => '',
							'port' => '',
							'scheme' => '',
							'path' => '/',
							'user' => '',
							'pass' => ''
						]],
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
