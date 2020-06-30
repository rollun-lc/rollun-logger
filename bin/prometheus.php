<?php

use rollun\logger\LifeCycleToken;
use rollun\logger\Writer\PrometheusWriter;
use Psr\Log\LoggerInterface;

error_reporting(E_ALL ^ E_USER_DEPRECATED ^ E_DEPRECATED);

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/** @var \Interop\Container\ContainerInterface $container */
$container = require 'config/container.php';

$lifeCycleToken = LifeCycleToken::generateToken();
$container->setService(LifeCycleToken::class, $lifeCycleToken);

/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

$data = [
    'metricId' => 'metric_25',
    'value'    => 1,
    'groups'   => ['group1' => 'val1'],
    'method'   => PrometheusWriter::METHOD_POST,
//    'refresh'  => true,
];

$logger->notice('METRICS_COUNTER', $data);

echo 'Done !';
die();