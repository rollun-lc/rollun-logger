# Фільтрація логів в залежності від їх кількості

> **⚠️ DEPRECATED WARNING**
>
> Цей функціонал позначений як deprecated з версії 7.8.0 і буде видалений в версії 8.0.0.
>
> **Причина:** функціонал CountPerTime та пов'язана залежність від laminas-cache більше не використовуються та створюють непотрібні залежності.

Проблема - деякі логи заслуговують уваги тільки якщо їх багато. Рішення - додати процессор, який буде рахувати логи за певний час, і вважати їх помилкою після певного трешхолду.

Процессор CountPerTime перевіряє, чи було логів більше певної кількості за останній час, і в залежності від цього викликає перелік процессорів, які були передані в полях onTrue або onFalse. (onTrue - к-ть перевищена, onFalse - ні)

## Підключення

Для підрахунку логів використовується редіс, тому треба вказати його в env:

```php
LOGS_REDIS_HOST=localhost
LOGS_REDIS_PORT=6379 // необов'язково
```

Також треба підключити абстрактні фабрики:

```php
'log_processors' => [
    'abstract_factories' => [
        ProcessorAbstractFactory::class,
        ConditionalProcessorAbstractFactory::class,
    ],
],
```

## Приклад використання

Конфіги на вищому рівні поряд з dependencies:

```php
use rollun\logger\Processor\ChangeLevel;
use rollun\logger\Processor\CountPerTime;
use rollun\logger\Processor\Factory\ConditionalProcessorAbstractFactory;
use rollun\logger\Processor\Factory\ProcessorAbstractFactory;
use rollun\logger\Processor\ProcessorWithCount;

###########

ConditionalProcessorAbstractFactory::KEY => [
    'FilterSomeError' => [
        ConditionalProcessorAbstractFactory::KEY_FILTERS => [
            [
                'name' => 'priority', // фільтруємо логи по рівню
                'options' => [
                    'operator' => '>=',
                    'priority' => 3,
                ],
            ],
            [
                'name' => 'regex', // фільтруємо логи по меседжу
                'options' => [
                    'regex' => '/^Some error message\.$/'
                ],
            ],
        ],
        ConditionalProcessorAbstractFactory::KEY_PROCESSORS => [
            [
                'name' => CountPerTime::class,
                'options' => [
                    'onTrue' => [
                        [
                            'name' => 'ChangeErrorToWarning', // процессор при перевищенні к-ті
                        ],
                    ],
                    'onFalse' => [
                        [
                            'name' => ProcessorWithCount::class, // процессор, якщо к-ть не перевищено - просто логує поточну к-ть
                        ],
                    ],
                ],
            ],
        ],
    ],
],
ProcessorAbstractFactory::KEY => [
    'ChangeErrorToWarning' => [
        'name' => ChangeLevel::class, // процессор для зміни рівня логу
        'options' => [
            'from' => LogLevel::ERROR,
            'to' => LogLevel::WARNING,
        ],
    ],
],
'log' => [
    LoggerInterface::class => [
        'processors' => [
            [
                'name' => 'FilterSomeError', // підключення фільтру для логеру
            ],
        ],
    ],
],
```

## Приклад використання для підвищення рівню логів

```php
use rollun\logger\Processor\ChangeLevel;
use rollun\logger\Processor\CountPerTime;
use rollun\logger\Processor\Factory\ConditionalProcessorAbstractFactory;
use rollun\logger\Processor\Factory\ProcessorAbstractFactory;
use rollun\logger\Processor\ProcessorWithCount;

###########

ConditionalProcessorAbstractFactory::KEY => [
    'OpenapiNot2xx' => [
        ConditionalProcessorAbstractFactory::KEY_FILTERS => [
            [
                'name' => 'regex',
                'options' => [
                    'regex' => '/^Openapi not 2xx response received\.$/'
                ],
            ],
        ],
        ConditionalProcessorAbstractFactory::KEY_PROCESSORS => [
            [
                'name' => 'ChangeInfoToError',
            ],
        ],
    ],
],
ProcessorAbstractFactory::KEY => [
    'ChangeInfoToError' => [
        'name' => ChangeLevel::class,
        'options' => [
            'from' => LogLevel::INFO,
            'to' => LogLevel::ERROR,
        ],
    ],
],
'log' => [
    LoggerInterface::class => [
        'processors' => [
            [
                'name' => 'OpenapiNot2xx',
            ],
        ],
    ],
],
```