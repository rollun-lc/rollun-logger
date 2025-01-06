<?php

use Elasticsearch\ClientBuilder;
use rollun\logger\LifeCycleToken;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';
/** @var \Laminas\ServiceManager\ServiceManager $container */
$container = require "config/container.php";
$lifeCycleToken = LifeCycleToken::generateToken();
$container->setService(LifeCycleToken::class, $lifeCycleToken);


$client = ClientBuilder::create()
	->setHosts([[
		'host' => '',
		'port' => '',
		'scheme' => '',
		'path' => '',
		'user' => '',
		'pass' => ''
	]])->build();

/*$params = [
	'index' => 'my_index',
	'type' => 'my_type',
	'id' => 'my_id',
	'body' => ['testField' => 'abc']
];
$result = $client->index($params);
print_r($result);*/
/*
$params = [
	'index' => 'my_index',
	'type' => 'my_type',
	'id' => 'my_id'
];

$response = $client->get($params);
print_r($response);*/

$params = [
	'index' => 'rollun_log',
	'type' => '_doc',
	'size' => 50,               // how many results *per shard* you want back
	'body' => [
		'query' => [
			'match_all' => new \stdClass()
		]
	]
];

$response = $client->search($params);
print_r($response);