<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 19.12.16
 * Time: 11:45 AM
 */

use \rollun\logger\Logger;
use \rollun\logger\LoggerFactory;
use \rollun\logger\LogWriter\FileLogWriter;
use \rollun\logger\LogWriter\FileLogWriterFactory;
use \rollun\installer\Command;
use \rollun\logger\LoggerInstaller as LoggerInstaller;
use rollun\logger\LogWriter\LogWriterInterface;

return [
    'dependencies' => [
        'factories' => [
            \rollun\logger\LogWriter\FileLogWriter::class => \rollun\logger\LogWriter\FileLogWriterFactory::class,
            \rollun\logger\Logger::class => \rollun\logger\LoggerFactory::class,
        ],
        'aliases' => [
            LogWriterInterface::DEFAULT_LOG_WRITER_SERVICE => FileLogWriter::class,
            Logger::DEFAULT_LOGGER_SERVICE => Logger::class,
        ]
    ]
];
