<?php

use Psr\Log\LoggerInterface;
use rollun\logger\Processor\IdMaker;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Log\Writer\Mock as WriterMock;
use Zend\Log\LoggerAbstractServiceFactory;
use Zend\Log\LoggerServiceFactory;
use Zend\Log\FilterPluginManagerFactory;
use Zend\Log\FormatterPluginManagerFactory;
use Zend\Log\ProcessorPluginManagerFactory;
use Zend\Log\WriterPluginManagerFactory;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream as WriterStream;
use Zend\Log\Writer\Db as WriterDb;
use Zend\Log\Formatter\Simple as FormatterSimple;
use rollun\logger\Formatter\ContextToString;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\AdapterAbstractServiceFactory;

return [
    'db' => [
        'adapters' => [
            AdapterInterface::class => [
                'driver' => 'Pdo_Mysql',
                'database' => 'logs_db',
                'username' => 'root',
                'password' => '',
            ],
        ],
    ],
    'log_formatters' => [
        'factories' => [
            ToString::class => InvokableFactory::class
        ],
    ],
    'log_filters' => [
        'factories' => [
// ...
        ],
    ],
    'log_processors' => [
        'factories' => [
            IdMaker::class => InvokableFactory::class
        ],
    ],
    'log_writers' => [
        'factories' => [
// ...
        ],
    ],
    'dependencies' => [
        'abstract_factories' => [
            LoggerAbstractServiceFactory::class,
            AdapterAbstractServiceFactory::class,
        ],
        'factories' => [
            Logger::class => LoggerServiceFactory::class,
            'LogFilterManager' => FilterPluginManagerFactory::class,
            'LogFormatterManager' => FormatterPluginManagerFactory::class,
            'LogProcessorManager' => ProcessorPluginManagerFactory::class,
            'LogWriterManager' => WriterPluginManagerFactory::class,
        ],
        'aliases' => [
            'logDbAdapter' => AdapterInterface::class, //logWithDbWriter
        ],
    ],
    'log' => [
        LoggerInterface::class => [
            'processors' => [
                [
                    'name' => IdMaker::class
                ],
            ],
            'writers' => [
                [
                    'name' => Mock::class,
//                  'priority' => Logger::DEBUG,
//                  'options' => [
//                        //'stream' => 'php://output',
//                        'formatter' => [
//                            'name' => 'MyFormatter',
//                            'options' => []
//                        ],
//                        'filters' => [
//                            [
//                                'name' => 'MyFilter',
//                            ],
//                        ],
//                  ],
                ],
            ],
        ],
        //
        'logWithMockWriter' => [
            'processors' => [
                [
                    'name' => IdMaker::class
                ],
            ],
            'writers' => [
                [
                    'name' => WriterMock::class,
                ],
            ],
        ],
        //
        'logWithFileWriter' => [
            'processors' => [
                [
                    'name' => IdMaker::class
                ],
            ],
            'writers' => [
                [
                    'name' => WriterStream::class,
                    'options' => [
                        'stream' => 'data/log/test-log.txt',
                        'formatter' => [
                            'name' => FormatterSimple::class,
                            'format' => '%id% %timestamp% %level% %message% %context%'
                        ],
                    ],
                ],
            ],
        ],
        //
        'logWithDbWriter' => [
            'processors' => [
                [
                    'name' => IdMaker::class
                ],
            ],
            'writers' => [
                [
                    'name' => WriterDb::class,
                    'options' => [
                        'db' => 'logDbAdapter',
                        'table' => 'logs',
                        'column' => [
                            'id' => 'id',
                            'timestamp' => 'timestamp',
                            'message' => 'message',
                            'level' => 'level',
                            'priority' => 'priority',
                            'context' => 'context',
                        ],
                        'formatter' => ContextToString::class
                    ],
                ],
            ],
        ],
    ],
];