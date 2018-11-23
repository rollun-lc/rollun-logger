# rollun-logger

The Logger class implements the interface `Psr\Log\LoggerInterface`

To run the tests, configure the access to the database in `config\autoload\logger.development.test.global`

and create a table in the database using the script `src\create_table_logs.sql`

## Usage
For safe use of the logger, you need to add the following lines to index.php (and all entry point scripts):
```php
<?php

/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
call_user_func(function () {
    //init lifecycle token
    $lifeCycleToken = \rollun\logger\LifeCycleToken::generateToken();
    if (\rollun\logger\LifeCycleToken::getAllHeaders() && array_key_exists("LifeCycleToken", \rollun\logger\LifeCycleToken::getAllHeaders())) {
        $lifeCycleToken->unserialize(\rollun\logger\LifeCycleToken::getAllHeaders()["LifeCycleToken"]);
    }
    /** use container method to set service.*/
    /** @var \Interop\Container\ContainerInterface $container */
    $container = require "config/container.php";
    $container->setService(\rollun\logger\LifeCycleToken::class, $lifeCycleToken);
    try {
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
    } catch (ContainerException $containerException) {
        $logger = new \rollun\logger\SimpleLogger();
        $logger->error($containerException);
        $container->setService(\Psr\Log\LoggerInterface::class, $logger);
    }
    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $logger->notice("Test notice. %request_time", ["request_time" => $_SERVER["REQUEST_TIME"]]);
});
```

## Config
```php
<?php

return
    [
        'log_formatters' => [
            'factories' => [
                'rollun\logger\Formatter\ContextToString' => 'Zend\ServiceManager\Factory\InvokableFactory',
            ],
        ],
        'log_filters' => [
            'factories' => [
            ],
        ],
        'log_processors' => [
            'factories' => [
                'rollun\logger\Processor\IdMaker' => 'Zend\ServiceManager\Factory\InvokableFactory',
                'rollun\logger\Processor\LifeCycleTokenInjector' => 'rollun\logger\Processor\Factory\LifeCycleTokenReferenceInjectorFactory',
            ],
        ],
        'log_writers' => [
            'factories' => [
            ],
        ],
        'dependencies' => [
            'abstract_factories' => [
                'Zend\Log\LoggerAbstractServiceFactory',
                'Zend\Db\Adapter\AdapterAbstractServiceFactory',
            ],
            'factories' => [
                'Zend\Log\Logger' => 'Zend\Log\LoggerServiceFactory',
                'LogFilterManager' => 'Zend\Log\FilterPluginManagerFactory',
                'LogFormatterManager' => 'Zend\Log\FormatterPluginManagerFactory',
                'LogProcessorManager' => 'Zend\Log\ProcessorPluginManagerFactory',
                'LogWriterManager' => 'Zend\Log\WriterPluginManagerFactory',
            ],
            'aliases' => [
                'logDbAdapter' => 'Zend\Db\Adapter\AdapterInterface',
            ],
        ],
        'log' => [
            'Psr\Log\LoggerInterface' => [
                'processors' => [
                    [
                        'name' => 'rollun\logger\Processor\IdMaker',
                    ],
                    [
                        'name' => 'rollun\logger\Processor\LifeCycleTokenInjector',
                    ],
                ],
                'writers' => [
                    [
                        'name' => 'Zend\Log\Writer\Db',
                        'options' => [
                            'db' => 'logDbAdapter',
                            'table' => 'logs_test_log',
                            'column' => [
                                'id' => 'id',
                                'timestamp' => 'timestamp',
                                'message' => 'message',
                                'level' => 'level',
                                'priority' => 'priority',
                                'context' => 'context',
                                'lifecycle_token' => 'lifecycle_token',
                            ],
                            'formatter' => 'rollun\logger\Formatter\ContextToString',
                        ],
                    ],
                ],
            ],
        ],
    ];
```

### [Processors](https://docs.zendframework.com/zend-log/processors/)

* ExceptionBacktrace - достает с `$context`, обрабатывает `exception` объект и помещает результат под ключем `backtrace`

Пример:

```php

// According to psr-3 standard put exception under 'exception' key
$previousException = new \Exception('Previous error', 1)
$event['context']['exception'] = new \Exception('Error eccurred', 2, $previousException)
$processor = new ExceptionBacktrace();
$event = $processor->process($event);

print_r($event['context']['backtrace']);
// Output
// [
//     [
//         'line' => 22,
//         'file' => 'someFile.php',
//         'code' => 2,
//         'message' => 'Error eccurred',
//     ],
//     [
//         'line' => 34,
//         'file' => 'someElseFile.php',
//         'code' => 1,
//         'message' => 'Previous error',
//     ],
// ]

```
