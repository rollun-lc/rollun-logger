<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 19.12.16
 * Time: 11:45 AM
 */

use \rollun\logger\Logger;
use \rollun\logger\LoggerFactory;
use \rollun\logger\LogWriter\FileLogWriterInterface;
use \rollun\logger\LogWriter\FileLogWriterFactory;
use \rollun\installer\Command;
use \rollun\logger\Installer as LoggerInstaller;

return [
    'dependencies' => [
        'factories' => [
            \rollun\logger\LogWriter\FileLogWriterInterface::class => \rollun\logger\LogWriter\FileLogWriterFactory::class,
            \rollun\logger\Logger::class => \rollun\logger\LoggerFactory::class,
        ],
        'aliases' => [
            'logWriter' => FileLogWriterInterface::class,
            'logger' => Logger::class,
        ]
    ]
];
