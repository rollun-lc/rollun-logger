# rollun-logger

`rollun-logger` - логгер, который во многом основан на [zendframework/zend-log](https://github.com/zendframework/zend-log)
, но переписан для поддержки [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/).

### Установка

Установить с помощью [composer](https://getcomposer.org/).
```bash
composer require rollun-com/rollun-logger
```

В `config/config.php` в `ConfigAggregator` додати `\rollun\logger\ConfigProvider::class`

### Швидкий початок

```php
<?php
use Psr\Log\LoggerInterface;

// Use container method to set service
/** @var \Laminas\ServiceManager\ServiceManager $container */
//Або
/** @var \Laminas\ServiceManager\ServiceManager $container */
$container = require "config/container.php";

$logger = $container->get(LoggerInterface::class);
$logger->notice("Привіт світ!");
```

### Шпаргалка конфігурації

Переменные окружения:

    * Для логера:    
        - LOGSTASH_HOST - хост. logstash отправляет данные в elasticsearch.
        - LOGSTASH_PORT - порт.
        - LOGSTASH_INDEX - индекс. Рекомендуется писать то же название, что и в SERVICE_NAME только в ловеркейсе и через нижнее подчеркивание.
        
    * Для Jaeger:
        - TRACER_HOST - хост.
        - TRACER_PORT - порт.
        
    * Для метрики:    
        - METRIC_URL - урл метрики   
        - PROMETHEUS_HOST - хост Prometheus
        - PROMETHEUS_PORT - порт Prometheus. По умолчанию 9091
        - PROMETHEUS_REDIS_HOST - хост от Redis. Нужно указать если будет использоваться Redis адаптер для хранения.
        - PROMETHEUS_REDIS_PORT - порт от Redis. По умолчанию 6379
        
    * Для Slack:    
        - SLACK_TOKEN - Slack Bot User OAuth Access Token
        - SLACK_CHANNEL - Slack channel id   

### Getting Started

По скольку это расширение к `zend-log`, базовою документацию можно почитать 
[здесь](https://framework.zend.com/manual/2.4/en/modules/zend.log.overview.html).


#### Writes

- **Http** - логирует данные по указанному [URI](https://en.wikipedia.org/wiki/Uniform_Resource_Identifier) пути.
- **HttpAsync** - асинхронно логирует данные по указанному [URL](https://en.wikipedia.org/wiki/URL) пути.
- **HttpAsyncMetric** - расширяет HttpAsync и асинхронно пишет метрику по указанному [URL](https://en.wikipedia.org/wiki/URL) пути. Writer подключен по умолчанию и пишет логи на урл который указан в переменных окружения (METRIC_URL).
- **PrometheusWriter** - пишет метрику на Prometheus методом pushGateway. Для работы нужно указать PROMETHEUS_HOST, PROMETHEUS_PORT и SERVICE_NAME в переменных окружения. На данный момент поддерживается только тип метрики "Измеритель"(gauge) и "Счетчик"(counter). Для того чтобы использовался Redis адаптер для хранения данных нужно указать PROMETHEUS_REDIS_HOST и PROMETHEUS_REDIS_PORT в переменных окружения.
- **Slack** - пишет логи в Slack канал. Отправляться только сообщения с уровнем меньше чем 4 (меньше warning, например error). Для того чтобы бот писал сообщения в канал, его нужно добавить в тот канал который вам нужен. Для этого зайдите в Slack, откройте нужный вам канал, нажмите на кнопку `Add apps` и там выберите `RollunApp`. Также нужно указать переменные окружения которые указаны выше для Slack.    

#### Formatters

- **ContextToString** - декодирует `$event` в `json`.
- **SlackFormatter** - добавляет в `$event` `slackMessage` поле, где подготовленное сообщение для Slack.


#### Processors

- **LifeCycleTokenInjector** - добавляет `LifeCycleToken` токены под соответственными ключами: 
    - `LifeCycleToken::KEY_LIFECYCLE_TOKEN` (`lifecycle_token`);
    - `LifeCycleToken::KEY_ORIGINAL_LIFECYCLE_TOKEN` (`original_lifecycle_token`);
    - `LifeCycleToken::KEY_PARENT_LIFECYCLE_TOKEN` (`parent_lifecycle_token`);
    - `LifeCycleToken::KEY_ORIGINAL_PARENT_LIFECYCLE_TOKEN` (`original_parent_lifecycle_token`).
- **IdMarker** - добавляет к массиву `$event` автосгенерированый идентификатор под ключом `id`.
- **ExceptionBacktrace** - достает с `$context`, обрабатывает `exception` объект и помещает результат под ключем 
`backtrace`.

Пример:

```php
<?php

use rollun\logger\Processor\ExceptionBacktrace;

// According to psr-3 standard put exception under 'exception' key
$previousException = new \Exception('Previous error', 1);
$event['context']['exception'] = new \Exception('Error eccurred', 2, $previousException);
$processor = new ExceptionBacktrace();
$event = $processor->process($event);

print_r($event['context']['backtrace']);
/* Output
[
    [
        'line' => 22,
        'file' => 'someFile.php',
        'code' => 2,
        'message' => 'Error eccurred',
    ],
    [
        'line' => 34,
        'file' => 'someElseFile.php',
        'code' => 1,
        'message' => 'Previous error',
    ],
]
*/
```

### LifeCycleToken

`LifeCycleToken` - это объект который генерирует токены для определения приложения в котором была залогирована
информация. 

#### **Передача LCT.** 
Клиенты передают **родительский** LCT одним HTTP-заголовком: `Lifecycle-Token: <token>` (допустимы имена-синонимы: `LifecycleToken`, `Life-Cycle-Token`; регистр имени не важен).

#### **Распознавание.** 
`LifeCycleToken::createFromHeaders()` ищет заголовок в `$_SERVER` по ключам `HTTP_LIFECYCLE_TOKEN`, `HTTP_LIFECYCLETOKEN`, `HTTP_LIFE_CYCLE_TOKEN` и, если найден, создаёт новый токен, установив найденное значение как **parent**. *(Заголовки с подчёркиваниями по умолчанию Nginx отбрасывает.)*

**Для версий выше 5.2.1 (включительно)**

LifeCycleToken подключается автоматически через конфигурацию. Если вы загружаете конфигурацию с ```rollun\logger\ConfigProvider```
(проверить можно в файле config/config.php),то вам ничего не нужно делать, токен уже настроен. Иначе добавьте в контейнер
фабрику ```rollun\logger\Factory\LifeCycleTokenFactory``` под ключом ```rollun\logger\LifeCycleToken::class```, например:

```php
use rollun\logger\LifeCycleToken;
use rollun\logger\Factory\LifeCycleTokenFactory;

return [
    'dependencies' => [
        'factories' => [
            LifeCycleToken::class => LifeCycleTokenFactory::class
        ]
    ],
];
```

**Для версии ниже 5.2.1**


Для того чтобы использовать `LifeCycleToken` приложении нужно добавить следуйщий код в `index.php` в Вашем
приложении.

```php
<?php
use Psr\Log\LoggerInterface;
use rollun\logger\LifeCycleToken;
use rollun\logger\SimpleLogger;

// Self-called anonymous function that creates its own scope and keep the global namespace clean
call_user_func(function () {
    // Init lifecycle token
    $lifeCycleToken = LifeCycleToken::generateToken();

    if (LifeCycleToken::getAllHeaders() && array_key_exists("LifeCycleToken", LifeCycleToken::getAllHeaders())) {
        $lifeCycleToken->unserialize(LifeCycleToken::getAllHeaders()["LifeCycleToken"]);
    }

    // Use container method to set service
    /** @var \Laminas\ServiceManager\ServiceManager $container */
    $container = require "config/container.php";
    $container->setService(LifeCycleToken::class, $lifeCycleToken);

    try {
        $logger = $container->get(LoggerInterface::class);
    } catch (\Psr\Container\ContainerExceptionInterface $containerException) {
        $logger = new SimpleLogger();
        $logger->error($containerException);
        $container->setService(LoggerInterface::class, $logger);
    }

    $logger = $container->get(LoggerInterface::class);
    $logger->notice("Test notice. %request_time", ["request_time" => $_SERVER["REQUEST_TIME"]]);
});
```



### Конфигурация

#### Добавление своих фильтров
Фильтры добавляются к конкретному врайтеру, и конфигурация может отличаться у различных врайтеров. Если возникают ошибки,
то смотрите конструктор врайтера и ищите там как должен добавляться фильтр. 

Все врайтеры из библиотеки rollun-logger наследуются от [rollun\logger\Writer\AbstractWriter](./../src/Logger/src/Writer/AbstractWriter.php)
и потому имеют однообразный способ добавления фильтров (что рекомендуется делать и для сторонних врайтеров). Рассмотрим его.

Конфигурация врайтера с фильтром выглядит следующим образом

```php
return [
    'log' => [
        \Psr\Log\LoggerInterface::class => [
            'writers'    => [
                'stream_stdout' => [
                    // writer className
                    'name'    => \rollun\logger\Writer\Stream::class,
                    
                    // options that passed to writer constructor
                    'options' => [
                        'stream'    => 'php://stdout',
                        
                        // optional: your plugin manager for filters
                        'filter_manager' => \rollun\logger\FilterPluginManager::class,
                        
                        'filters' => [
                            'priority_<=_7' => [
                                // filter className or alias (from plugin manager)
                                'name'    => 'priority',
                                
                                // options that passed to filter constructor
                                'options' => [
                                    'operator' => '<=',
                                    'priority' => 7,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

По умолчанию все фильтры создаются через плагин менеджер, так что в идеальной ситуации чтобы добавить свой кастомный класс
фильтра нужно переопределить плагин менеджер (через опцию 'filter_manager'), например можно унаследоваться от базового
плагин менеджера. Например:


```php
<?php

namespace rollun\logger;

use rollun\logger\Filter\TestFilter;
use Zend\ServiceManager\Factory\InvokableFactory;

class TestFilterPluginManager extends \rollun\logger\FilterPluginManager
{
    public function __construct($configInstanceOrParentLocator = null, array $config = [])
    {
        parent::__construct($configInstanceOrParentLocator, $config);
        $this->aliases['test'] = TestFilter::class;
        $this->factories[TestFilter::class] = InvokableFactory::class;
    }
}
```

Таким образом в конфигурации можно под ключом 'name' (в фильтрах) указывать 'test' и этот фильтр найдется,
через плагин менеджер.

Но чтобы не нужно было всегда переопределять плагин менеджер можно просто в 'name' указать полное имя класса, и если
такого класса нету в плагин менеджере, то он по умолчанию попытается создаться через ```Laminas\ServiceManager\Factory\InvokableFactory```.
Например конфигурация для класса ```\rollun\logger\Filter\TestFilter``` которого нету в плагин менеджере:

```php
return [
    'log' => [
        \Psr\Log\LoggerInterface::class => [
            'writers'    => [
                'stream_stdout' => [
                    'name'    => \rollun\logger\Writer\Stream::class,
                    'options' => [
                        'stream'    => 'php://stdout',
                        'filters' => [
                            'test' => [
                                // will be created by Zend\ServiceManager\Factory\InvokableFactory
                                'name'    => \rollun\logger\Filter\TestFilter::class,
                                
                                // options that passed to filter constructor
                                'options' => [
                                    'option1' => 'something'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

#### Пример конифгурации

Для того чтобы начать быстро использовать логер в Вашем приложении, нужно внести следующие конфигурации в 
конфигурационный файл для [Service Manager](https://github.com/zendframework/zend-servicemanager).
```php
<?php
use rollun\logger\Formatter\ContextToString;
use rollun\logger\FormatterPluginManager;
use rollun\logger\Logger;
use rollun\logger\LoggerAbstractServiceFactory;
use rollun\logger\LoggerServiceFactory;
use rollun\logger\Processor\Factory\LifeCycleTokenReferenceInjectorFactory;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Processor\LifeCycleTokenInjector;
use rollun\logger\ProcessorPluginManager;
use rollun\logger\Writer\Db;
use rollun\logger\Writer\Stream;
use rollun\logger\WriterPluginManagerFactory;
use Zend\Db\Adapter\AdapterAbstractServiceFactory;
use Zend\Db\Adapter\AdapterInterface;
use rollun\logger\FilterPluginManagerFactory;
use Zend\ServiceManager\Factory\InvokableFactory;


return
    [
        'log_formatters' => [
            'factories' => [
                'rollun\logger\Formatter\ContextToString' => InvokableFactory::class,
            ],
        ],
        'log_filters' => [
            'factories' => [
            ],
        ],
        'log_processors' => [
            'factories' => [
                IdMaker::class => InvokableFactory::class,
                LifeCycleTokenInjector::class => LifeCycleTokenReferenceInjectorFactory::class,
            ],
        ],
        'log_writers' => [
            'factories' => [
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
                'LogFormatterManager' => FormatterPluginManager::class,
                'LogProcessorManager' => ProcessorPluginManager::class,
                'LogWriterManager' => WriterPluginManagerFactory::class,
            ],
            'aliases' => [
                'logDbAdapter' => AdapterInterface::class,
            ],
        ],
        'log' => [
            'Psr\Log\LoggerInterface' => [
                Logger::FALLBACK_WRITER_KEY => [
                    'name' => Stream::class,
                    'options' => [
                        'stream' => 'data/fallback.log',
                    ],
                ],
                'processors' => [
                    [
                        'name' => IdMaker::class,
                    ],
                    [
                        'name' => LifeCycleTokenInjector::class,
                    ],
                ],
                'writers' => [
                    'db_logs_test_log' => [
                        'name' => Db::class,
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
                            'formatter' => ContextToString::class,
                        ],
                    ],
                ],
            ],
        ],
    ];
```

### DB Writer

Для записи логов в mysql нужно запустить команду

```sql
create table if not exists <service_db>.logs
(
id                     varchar(255) not null
primary key,
timestamp              varchar(32)  not null,
level                  varchar(32)  not null,
priority               int          not null,
lifecycle_token        varchar(32)  not null,
parent_lifecycle_token varchar(32)  null,
message                mediumtext   not null,
context                mediumtext   not null
);
create index lifecycle_token
on <service_db>.logs (lifecycle_token);
create index parent_lifecycle_token
on <service_db>.logs (parent_lifecycle_token);
```



### Переопределение врайтеров в конфигурации
Чтобы была возможность мержить и переопределять конфигурацию врайтеров, то врайтеры и фильтры нужно добавлять под строчными ключами. 
Т.е вместо:
```php
use Psr\Log\LoggerInterface;
use rollun\logger\Writer\Stream;

return [
    'log' => [
        LoggerInterface::class => [
            'writers' => [
                [
                    'name'    => Stream::class,
                    'options' => [
                        'stream'    => 'php://stdout',
                        'filters'   => [
                            [
                                'name'    => 'priority',
                                'options' => [
                                    'operator' => '<=',
                                    'priority' => 4,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];
```
Нужно писать (добавлены ключи 'stream_stdout' и 'priority_<=_4'):
```php
use Psr\Log\LoggerInterface;
use rollun\logger\Writer\Stream;

return [
    'log' => [
        LoggerInterface::class => [
            'writers' => [
                'stream_stdout' => [
                    'name'    => Stream::class,
                    'options' => [
                        'stream'    => 'php://stdout',
                        'filters'   => [
                            'priority_<=_4' => [
                                'name'    => 'priority',
                                'options' => [
                                    'operator' => '<=',
                                    'priority' => 4,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];
```
При таком расскладе можно переопределять врайтер (или фильтр внутри врайтера) по его ключу (при этом надо быть осторожным, 
ибо на самом деле происходит слияние конфигурации и могут переопределиться не все параметры).  

Что бы убрать ненужный фильтр, его можно переопределить на null. Это сработает если врайтер наследуеться от 
[rollun\logger\Writer\AbstractWriter](https://github.com/avz-cmf/zend-psr3-log/blob/master/src/Writer/AbstractWriter.php#L95)
и не переопределяет логику родительского конструктора по добавлению врайтеров, которая находиться 
[тут](https://github.com/avz-cmf/zend-psr3-log/blob/74dc8053bc4c4ef520f1c9b42e2d0951b14de981/src/Writer/AbstractWriter.php#L95)  

Переопределить врайтер на null не получится, ибо тогда вылетит [исключение](https://github.com/avz-cmf/zend-psr3-log/blob/74dc8053bc4c4ef520f1c9b42e2d0951b14de981/src/Logger.php#L177)
в конструкторе логера при попытке добавить этот врайтер.

Ключи для логеров желательно именновать в snake case, с префиксом в виде именни класса, который указан в параметре 'name'
(без неймспейса, в случае конфликта имен можно придумать алиас), и, по возможности, после префикса короткое описание 
врайтера для уникальности ключа, это может быть либо **куда** либо **что** записывает данный, конкретный врайтер. 
Например 'udp_logstash' или 'prometheus_metrics_counter'.

Ключи для фильтров писать с префиксом в виде названия фильтра, и после короткое описание, которое отражает суть этого конкретного фильтра.
Например 
```php
'priority_<=_4' => [
    'name'    => 'priority',
    'options' => [
        'operator' => '<=',
        'priority' => 4,
    ]
]
```
или
```php
'regex_only_metrics_counter' => [
    'name'    => 'regex',
    'options' => [
        'regex' => '/^METRICS_COUNTER$/'
    ],
]
```

Главное что бы имя врайтеров было уникальным, но не слишком длинным. 
А имена фильтров были уникальны внутри одного конкретного врайтера.

### Jaeger tracing
С помощью Jaeger мы выполняем трассировку сервисов для отладки. Для хранения трейсов используется ElasticSearch.
Для подключения необходимо установить несколько переменных окружения:
 * SERVICE_NAME **обязательно** - для определения кто оправляет трейс
 * TRACER_HOST **обязательно** - для определения на какой хост отправить трейс
 * TRACER_PORT **не обязательно** - по умолчанию 6832. Для определения на какой порт отправлять трейс
 * TRACER_DEBUG_ENABLE **не обязательно** - по умолчанию включен. Трейсы пишуться только при включенном параметре. По сути этот параметр влияет на настройки [sampling](https://www.jaegertracing.io/docs/1.17/sampling/#client-sampling-configuration). 
 
 смотри [пример](../.env.dist).
 
Пример использования
```php
<?php
declare(strict_types=1);

namespace App\Handler;

use Jaeger\Log\ErrorLog;
use Jaeger\Tag\ErrorTag;
use Jaeger\Tag\StringTag;
use Jaeger\Tracer\Tracer;
use rollun\dic\InsideConstruct;

/**
 * Class Foo
 */
class Foo
{
    /**
     * @var Tracer
     */
    protected $tracer;

    public function __construct(Tracer $tracer = null)
    {
        InsideConstruct::init(
            [
                'tracer' => Tracer::class,
            ]
        );
    }

    public function run()
    {
        $this->operationA();
    }

    protected function operationA()
    {
        $span = $this->tracer->start('Operation A', [new StringTag('description', 'Hello world A!')]);

        // adding string tag
        $span->addTag(new StringTag('shortDesc', 'Hello world!!!'));

        $this->operationB();

        $this->tracer->finish($span);

    }

    protected function operationB()
    {
        $span = $this->tracer->start('Operation B', [new StringTag('description', 'Hello world B!')]);

        // adding error tag
        $span->addTag(new ErrorTag());

        // add error log
        $span->addLog(new ErrorLog('message 1', 'stack 1'));

        $this->tracer->finish($span);
    }
}
```
Результат:
![alt text](assets/img/tracer.png)
Из примера видно, что нужно открывать и закрывать операции, поддерживаются вложенные операции. Во время выполнения кода нужно добавлять разного рода теги для отладки. Тег нужен для быстрого поиска трейтов. В примере мы показали текстовый тег, тег ошибку, а также добавили лог ошибки. Библиотека поддерживает и другие [теги](https://github.com/code-tool/jaeger-client-php/tree/master/src/Tag).
 

### Метрика при помощи HttpAsyncMetric
Принято, что в метрику попадают только warning и notice. Также для метрик используется специальное название события.

Пример отправки метрик:
```php
$logger->warning('METRICS', ['metricId' => 'metric-1', 'value' => 100]);
// в результате будет отправлен асинхронный POST запрос на http://localhost/api/v1/Metric/metric-1 с телом {"value": 100,"timestamp": 1586881668}

$logger->notice('METRICS', ['metricId' => 'metric-2', 'value' => 200]);
// в результате будет отправлен асинхронный POST запрос на http://localhost/api/v1/Metric/metric-2 с телом {"value": 200,"timestamp": 1586881668}
```

### Метрика при помощи PrometheusWriter
Отправляет метрику в prometheus.

Пример как записать метрику. Пример использует конфиг который указан выше. В данном случае используется два типа метрик (измеритель, счетчик). 
```php
use rollun\logger\Writer\PrometheusWriter;

// Возможные настройки метрики
$data = [
    PrometheusWriter::METRIC_ID         => 'metric_25', // уникальное имя метрики
    PrometheusWriter::VALUE             => 1, // значение метрики
    PrometheusWriter::GROUPS            => ['group1' => 'val1'], // группы для которых пишется метрика. при помощи групп можно структурировать метрику. 
    PrometheusWriter::LABELS            => ['label1', 'label2'], // ярлыки метрики. используется если название метрики не достаточно и вы хотите использовать вспомогательные ярлыки.
    PrometheusWriter::METHOD            => PrometheusWriter::METHOD_POST, // способ отправки. Разница описана здесь https://github.com/prometheus/pushgateway#put-method
    PrometheusWriter::REFRESH           => true, // если вы хотите сбросить накопленное значение и начать заново нужно передать true. Имеет смысл только если вы используете тип counter. 
    PrometheusWriter::WITH_SERVICE_NAME => true, // если данная пара ключ-значение не добавлена или же значение true, и так же установлена переменная окружения SERVICE_NAME, метрика будет добавлена в группу со значением переменной SERVICE_NAME (PrometheusWriter::GROUPS => ['group1' => 'val1', 'service' => 'serviceName']).   
];

// измерители
$logger->notice('METRICS_GAUGE', [PrometheusWriter::METRIC_ID => 'metric_1', PrometheusWriter::VALUE => 50, PrometheusWriter::GROUPS => ['group1' => 'val1'], PrometheusWriter::LABELS => ['red']]);
$logger->notice('METRICS_GAUGE', [PrometheusWriter::METRIC_ID => 'metric_2', PrometheusWriter::VALUE => 12, PrometheusWriter::METHOD => PrometheusWriter::METHOD_PUT, PrometheusWriter::WITH_SERVICE_NAME => true]);

// счетчики
$logger->notice('METRICS_COUNTER', [PrometheusWriter::METRIC_ID => 'metric_3', PrometheusWriter::VALUE => 10, PrometheusWriter::GROUPS => ['group1' => 'val1'], PrometheusWriter::LABELS => ['red']]);
$logger->notice('METRICS_COUNTER', [PrometheusWriter::METRIC_ID => 'metric_4', PrometheusWriter::VALUE => 1, PrometheusWriter::REFRESH => true]);
```

## Запись в LogStash по TCP
Из-за ограничения датаграмы UDP в ~65 килобайт в версии 4.7 появился TCP writer для записи логов в logstash. 

Что бы использовать его достаточно переопределить у себя в конфигурации UDP врайтер (который под ключем 'udp_logstash'),
изменив 'name' на ```rollun\logger\Writer\TCP``` вместо ```rollun\logger\Writer\Udp``` (Подробнее про переопределения 
написано выше). А так же изменив порт по которому записываются логи в LogStash. Это можно сделать несколькими способами: 
создать новую переменную окружения и переопределить конфигурацию, либо просто написать новый порт в переменную окружения 
LOGSTASH_PORT (которая используется по умолчанию).

## Middleware
### RequestLoggedMiddleware
Используется для логгирования всех входящих запросов в формате `[Datetime] Method - URL <- Ip address`. Например: 
`[2020-11-12T10:15:02+00:00] GET - /api/webhook/cron?param=true <- 172.20.0.1`
Если вы используете ZendFramework, то подключается, как и все middleware, в файле config/pipeline:
```php
<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;

/**
 * Setup middleware pipeline:
 *
 * @param Application $app
 * @param MiddlewareFactory $factory
 * @param ContainerInterface $container
 * @return void
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    // Recommend to include after '$app->pipe(Zend\Stratigility\Middleware\ErrorHandler::class);'
    $app->pipe(\rollun\logger\Middleware\RequestLoggedMiddleware::class);
    // ... (other middlewares)
};
```

По умолчанию логгер берется из контенйера по ключу "Psr\Log\LoggerInterface", согласно фабрике [RequestLoggedMiddlewareFactory](https://github.com/rollun-com/rollun-logger/blob/fa35ffa8dca2f137d38fa3b66eb8fdc3fde5283a/src/Logger/src/Middleware/Factory/RequestLoggedMiddlewareFactory.php#L18),
которая подключается в `rollun\logger\ConfigProvider`. Если вам это не подходит вы всегда можете переопределить эту фабрику
в своей конфигурации.

### RecursiveJsonTruncator

#### Инструмент для «умного» усечения больших JSON-строк:
* **Первый проход**: рекурсивно «сжимает» слишком длинные списковые массивы (массивы с числовыми ключами 0..N-1). Оставляет первые N элементов и добавляет `…` в конец.
* **Проверка размера**: после первого прохода проверяется размер результата. Если он превышает `maxResultLength`, запускается второй проход.
* **Второй проход**: дополнительно обрезает ассоциативные массивы (с строковыми ключами). При обрезке ассоциативных массивов сохраняются первые и последние ключи, а в середину добавляется маркер `'…' => '…'` для понимания структуры.
* **Глубинный обход**: выполняет обход и, начиная с заданной глубины, превращает узел в строку (через `json_encode`) и обрезает строку до лимита, добавляя `…`, если нужно.
* **Защита от больших логов**: результат гарантированно не превысит `maxResultLength` (по умолчанию 100 КБ).

**Дополнительная защита в LogStashFormatter**: После обрезки контекста в `RecursiveJsonTruncator`, поле `context` проходит жесткую проверку размера в `LogStashFormatter`. Если `context` превышает `HARD_MAX_LOG_SIZE` (100 КБ), он обрезается принудительно с добавлением маркера `[TRUNCATED]`. Это гарантирует, что в ELK не попадут логи с контекстом больше допустимого размера.

#### Публичный контракт
* `__construct(RecursiveTruncationParams $params)`
* `withConfig(RecursiveTruncationParams $params): self` — возвращает клон с другими параметрами.
* `truncate(string $json): string` - принимает JSON-строку, возвращает JSON-строку. Если вход — невалидный JSON, бросает `InvalidArgumentException`

#### Параметры
Класс:
```php
rollun\logger\Services\RecursiveTruncationParams
```
**Поля**
* `maxLineLength` (по умолчанию: 1000) — лимит символов для строки при усечении (в т.ч. когда узел превращён в строку на глубине).
* `maxNestingDepth` (по умолчанию: 3) — максимальная глубина обхода. На глубине >= этого значения узел превращается в строку и при необходимости обрезается.
* `maxArrayToStringLength` (по умолчанию: 1000) — если `json_encode()` массива длиннее этого порога, массив сжимается (оставляем первые `maxArrayElementsAfterCut` элементов + `…`).
* `maxArrayElementsAfterCut` (по умолчанию: 10) — сколько элементов оставить при сжатии массива (включая маркер `'…'`). Для списковых массивов — первые N-1 элементов + маркер. Для ассоциативных — первые `ceil((N-1)/2)` ключей + маркер + последние `floor((N-1)/2)` ключей.
* `maxResultLength` (по умолчанию: 102400 = 100 КБ) — **новый параметр**. Жесткий лимит на размер результата. Если после первого прохода результат превышает этот лимит, запускается второй проход с обрезкой ассоциативных массивов.

Конструктор VO валидирует значения (минимумы/границы) и может быть создан из массива
```php
use rollun\logger\Services\RecursiveTruncationParams;

$params = RecursiveTruncationParams::createFromArray([
    'maxLineLength'            => 1000,
    'maxNestingDepth'          => 3,
    'maxArrayToStringLength'   => 1000,
    'maxArrayElementsAfterCut' => 10,
    'maxResultLength'          => 102400, // 100 КБ
]);

``` 

#### Пример использования
```php
use rollun\logger\Services\RecursiveJsonTruncator;
use rollun\logger\Services\RecursiveTruncationParams;

$params = RecursiveTruncationParams::createFromArray([
    'maxLineLength'            => 200,  // строковый лимит
    'maxNestingDepth'          => 2,    // глубже -> в строку
    'maxArrayToStringLength'   => 300,  // порог «сжатия» массива
    'maxArrayElementsAfterCut' => 3,    // оставляем 3 элемента + …
]);

$truncator = new RecursiveJsonTruncator($params);

$inputJson = json_encode([
    'meta' => ['veryLong' => str_repeat('x', 1000)],
    'items' => range(1, 50),
]);

$out = $truncator->truncate($inputJson);
// $out — валидный JSON, укороченный по описанным правилам

``` 

#### Конфигурация
Если отдельная конфигурация не задана, берутся значения по умолчанию. Задается таким образом:
```php
RecursiveJsonTruncatorFactory::class => [
                'maxLineLength'             => 1000,
                'maxNestingDepth'           => 3,
                'maxArrayToStringLength'    => 1000,
                'maxArrayElementsAfterCut'  => 10,
                'maxResultLength'           => 102400, // 100 КБ
            ],

``` 

#### Тонкости
* **Валидность JSON**: На входе ожидается валидный JSON (строка). Иначе — `InvalidArgumentException`. На выходе — всегда валидный JSON
* **Глубина**: При достижении `maxNestingDepth` узел превращается в строку (`json_encode` подузла) и, если нужно, обрезается до `maxLineLength`. Это значит, что числа/булевы/массивы/объекты на этой глубине станут строкой в итоговом JSON.
* **Двухпроходный алгоритм**:
  - Первый проход обрезает только списковые массивы (с числовыми индексами 0, 1, 2...).
  - Если результат превышает `maxResultLength`, запускается второй проход, который дополнительно обрезает ассоциативные массивы.
  - Это позволяет в большинстве случаев сохранить ассоциативные массивы целыми, обрезая их только при необходимости.
* **Обрезка ассоциативных массивов**: При обрезке сохраняются первые `ceil((N-1)/2)` ключей и последние `floor((N-1)/2)` ключей, где `N = maxArrayElementsAfterCut`. Между ними вставляется маркер `'…' => '…'`. Например, если `maxArrayElementsAfterCut = 4`, из массива с ключами `['a', 'b', 'c', 'd', 'e', 'f']` останутся `['a', 'b', '…' => '…', 'f']` (2 первых, маркер, 1 последний = 4 элемента). Все остальные ключи между первыми и последними удаляются.
* **Защита в LogStashFormatter**: Даже если `RecursiveJsonTruncator` не смог уложиться в лимит, `LogStashFormatter` применит жесткую обрезку поля `context` на уровне 100 КБ (`HARD_MAX_LOG_SIZE`). Проверка выполняется после работы `RecursiveJsonTruncator` и обрезает именно контекст, а не весь финальный JSON.
* `…` — Unicode-многоточие (U+2026).
* `null` сохраняется как `null` (не как строка `"null"`), пока узел не превратился в строку из-за глубины — тогда узел станет строкой (`"null"`), и к нему применится лимит
