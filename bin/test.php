<?php

use Psr\Log\LoggerInterface;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$container = require 'config/container.php';

$logger = $container->get(LoggerInterface::class);

try {
    $logger->error('TEST');
} catch (\Exception $e) {
    $stop = 2;
}

$stop = 2;

