<?php

global $argv;

use Symfony\Component\Dotenv\Dotenv;

error_reporting(E_ALL);

// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

// Make environment variables stored in .env accessible via getenv(), $_ENV or $_SERVER.
if (file_exists('.env')) {
    (new Dotenv())->load('.env');
}

if (getenv("APP_ENV") != 'test') {
    echo "You cannot start test if environment var APP_ENV not set in test!";
    exit(1);
}

// Setup autoloading
require 'vendor/autoload.php';
//require_once 'config/env_configurator.php';
