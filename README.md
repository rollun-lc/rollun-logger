#rollun-logger

Logger класс реализует интерфейс `Psr\Log\LoggerInterface`

Для запуска тестов настройте доступ к БД в config\autoload\logger.development.test.global

и создайте таблицу в БД скриптом create_table_logs

# Lifecycle token 
Для использования lifecycle token необходимо добавить в index.php
```php
<?php
    //init lifecycle token
    $lifeCycleToken = \rollun\logger\LifeCycleToken::generateToken();
    if(apache_request_headers() && array_key_exists("LifeCycleToken", apache_request_headers())) {
        $lifeCycleToken->unserialize(apache_request_headers()["LifeCycleToken"]);
    }
    /** use container method to set service.*/
    $container->setService(\rollun\logger\LifeCycleToken::class, $lifeCycleToken);

```

# Logger
Для безопасного использования логера, необходимо добавить в index.php (и все скрипты-запуска) следующие строки
```php
<?php
        
    /** @var \Interop\Container\ContainerInterface $container */
    try {
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
    } catch (ContainerException $containerException) {
        $logger = new \rollun\logger\SimpleLogger();
        $logger->error($containerException);
        $container->setService(\Psr\Log\LoggerInterface::class, $logger);
    }
```

# Config
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
                            ],
                            'formatter' => 'rollun\logger\Formatter\ContextToString',
                        ],
                    ],
                ],
            ],
        ],
    ];
```