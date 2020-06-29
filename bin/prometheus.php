<?php

use rollun\logger\LifeCycleToken;
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

$logger->notice('METRICS_GAUGE', ['metricId' => 'metric_1', 'value' => 25, 'groups' => ['group1' => 'val1'], 'labels' => ['red']]);

echo 'Done !';
die();