<?php
global $argv;

use PHPUnit\Framework\Error\Deprecated;

error_reporting(E_ALL);
Deprecated::$enabled = false;

// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

// Make environment variables stored in .env accessible via getenv(), $_ENV or $_SERVER.

if (getenv("APP_ENV") != 'dev') {
    echo "You cannot start test if environment var APP_ENV not set in dev!";
    exit(1);
}
