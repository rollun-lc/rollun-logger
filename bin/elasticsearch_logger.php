<?php

use Elasticsearch\ClientBuilder;
use rollun\logger\LifeCycleToken;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';
/** @var \Laminas\ServiceManager\ServiceManager $container */
$container = require "config/container.php";
$lifeCycleToken = LifeCycleToken::generateToken();
$container->setService(LifeCycleToken::class, $lifeCycleToken);

/** @var \Psr\Log\LoggerInterface $logger */
$logger = $container->get(\Psr\Log\LoggerInterface::class);
/*$logger->warning('tadam', [
	'data' => [
		'asda' => 123
	]
]);
$logger->info('tadam', [
	'asda' => 123,
	'data' => [
		'asda' => 123,
	]
]);
$logger->debug('tadam', [
	'data' => [
		'data1' => [
			'asda' => 123,
		]
	]
]);*/
$logger->alert('tadam', [
	'data' => [

	],
	'da2ta' => [
		'asda' => 123,
	],
	'dat4a' => [
		'asda' => 123,
		'a3sda' => 123,
		'a3gsda' => 123,
	]
]);/*
$logger->emergency('tadam', [
	'data' => [

		'da2ta' => [
			'asda' => 123,
		],
		'dat4a' => [
			'asda' => 123,
			'a3sda' => 123,
			'a3gsda' => 123,
		]
	]
]);
$logger->error('tadam', [
	'data' => [

		'da2ta' => [
			'asda' => 123,

			'da2ta' => [
				'asda' => 123,
			],
			'dat4a' => [
				'asda' => 123,
				'a3sda' => 123,
				'a3gsda' => 123,

				'da2ta' => [
					'asda' => 123,
				],
				'dat4a' => [
					'asda' => 123,
					'a3sda' => 123,
					'a3gsda' => 123,
				]
			]
		],
		'dat4a' => [
			'asda' => 123,
			'a3sda' => 123,
			'a3gsda' => 123,
		]
	]
]);
$logger->notice('tadam', [
	'data' => [

	],
	'asdasd' => 'adsdasd,',
	'asdas1d' => 'adsdasd,',
	'asdasd2' => 'adsdasd,',
	'asdas4d' => 'adsdasd,',
]);*/