<?php

use rollun\logger\LifeCycleToken;
use rollun\logger\Writer\PrometheusWriter;
use Psr\Log\LoggerInterface;

error_reporting(E_ALL ^ E_USER_DEPRECATED ^ E_DEPRECATED);

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/** @var \Laminas\ServiceManager\ServiceManager $container */
$container = require 'config/container.php';

$lifeCycleToken = LifeCycleToken::generateToken();
$container->setService(LifeCycleToken::class, $lifeCycleToken);

/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

$data = [
    'metricId' => 'metric_25',
    'value'    => 1,
    'groups'   => ['group1' => 'val1'],
    'labels'   => [],
    'method'   => PrometheusWriter::METHOD_POST,
    'refresh'  => true,
];

$logger->notice('METRICS_COUNTER', $data);

//$logger->notice(
//    'METRICS_COUNTER',
//    [
//        'metricId' => 'roma_inventory_change_1',
//        'value'    => 1,
//        'groups'   => [
//            'service'     => 'roma_service_test',
//            'marketplace' => 'ebay_plaisir',
//            'action'      => 'update',
//        ],
//    ]
//);
//
//$logger->notice(
//    "METRICS_COUNTER",
//    [
//        'metricId' => 'roma_marketplace_lot_change_send_1',
//        'value'    => 1,
//        'groups'   => [
//            'marketplace' => 'ebay_plaisir_inventory',
//            'service'     => 'roma_service_test',
//            'type'        => 'Observer',
//            'action'      => 'update'
//        ],
//    ]
//);

echo 'Done !';
die();