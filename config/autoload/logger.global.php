<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 19.12.16
 * Time: 11:45 AM
 */

use \rollun\logger\FileLogWriter;
use \rollun\logger\FileLogWriterFactory;
use \rollun\installer\Command;
use \rollun\logger\Installer as LoggerInstaller;

return [
    'logWriter' => [
        FileLogWriter::class => [
            FileLogWriterFactory::FILE_NAME_KEY =>
                realpath(Command::getPublicDir() . DIRECTORY_SEPARATOR .
                    LoggerInstaller::LOGS_DIR . DIRECTORY_SEPARATOR . LoggerInstaller::LOGS_FILE)
        ]
    ],
    'services' => [
        'factories' => [
            FileLogWriter::class => FileLogWriterFactory::class
        ],
        'aliases' =>[
            'logWriter' => FileLogWriter::class
        ]
    ]
];
