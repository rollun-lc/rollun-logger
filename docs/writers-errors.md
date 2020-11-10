# Обработка ошибок врайтеров
Все исключения которые возникают во врайтерах логгер пытается залогировать. 
По умолчанию ошибки врайтеров логируются с помощью стандартной php функции [error_log(...)](https://www.php.net/manual/ru/function.error-log.php).

Так же в конфигурации можно задать специальный врайтер `fallbackWriter`, который будет использоваться только для логирования
ошибок врайтеров, вместо error_log(...). Если ошибка произойдет и в `fallbackWriter`, то через error_log(...) залогируется и 
ошибка `fallbackWriter` и сообщение которое `fallbackWriter` пытался залогировать.

## Где искать логи записанные через error_log
Логгер записывает сообщения в [error_log(...)](https://www.php.net/manual/ru/function.error-log.php) с `message_type = 0`.
Это означает, что место куда запишутся логи определяется через директиву [error_log](https://www.php.net/manual/ru/errorfunc.configuration.php#ini.error-log)
в конфигурации `php.ini`. По умолчанию значение этой директивы равно NULL, что означает, что логи отправляются в SAPI журналы.
Основные виды SAPI в php: Apache2 (mod_php), FPM, CGI, FastCGI и CLI. Каждый из них может по-разному хранить логи и предоставлять
различные возможности конфигурации.

### Логи в PHP-FPM
Если вы оставили директиву error_log равной NULL (по умолчанию) и используете php-fpm, то место куда запишутся логи определяется
через директиву [error_log](https://www.php.net/manual/ru/install.fpm.configuration.php#error-log) в php-fpm.conf 
(не путать с error_log в php.ini). 

### Логи в PHP-FPM внутри docker
В стандартных образах php-fpm от docker значение error_log директивы [равно](https://github.com/docker-library/php/blob/5dd0da6ee3cede9a9f12e46fd5c58e96577aaafe/7.2/alpine3.12/fpm/Dockerfile#L208)
`'/proc/self/fd/2'`, что является выводом в [STDERR](https://docs.docker.com/config/containers/logging/).

Так же, по умолчанию, [включена](https://github.com/docker-library/php/blob/5dd0da6ee3cede9a9f12e46fd5c58e96577aaafe/7.2/alpine3.12/fpm/Dockerfile#L217)
опция [catch_workers_output](https://www.php.net/manual/ru/install.fpm.configuration.php#catch-workers-output), которая 
перехватывает вывод STDOUT и STDERR запущенных процессов php. Чтобы лучше понять значение этой опции, надо понимать, что
php-fpm это не интерпретатор php, а просто менеджер процессов php. Т.е. он внутри себя создает отдельные процессы интерпретатора
php, а директива catch_workers_output определяет будем ли мы перехватывать вывод этих процессов и записывать их в лог.

Таким образом вывод всех php процессов и их логи доступны в одном месте. Их можно посмотреть через [`docker logs`](https://docs.docker.com/engine/reference/commandline/logs/)


## Пример конфигурации `fallbackWriter`
Задать `fallbackWriter` в конфигурации можно под специальным ключом `Logger::FALLBACK_WRITER_KEY`.

Передача экземпляра объекта:
```php
use rollun\logger\Logger;
use Zend\Log\Writer\Stream;

$options = [
    Logger::FALLBACK_WRITER_KEY => [
        'name' => new Stream('php://output'),
    ]
];
$logger = new Logger($options);
```

Конфигурация через абстрактную фабрику:
```php
use rollun\logger\Logger;
use Zend\Log\Writer\Stream;
use rollun\logger\LoggerAbstractServiceFactory;

return [
    'dependencies' => [
        'abstract_factories' => [
            LoggerAbstractServiceFactory::class,
        ]
    ],
    'log' => [
        Logger::FALLBACK_WRITER_KEY => [
            'name' => Stream::class,
            'options' => [
                'stream' => 'php://output',
                'formatter' => [
                    'name' => 'MyFormatter',
                ],
                'filters' => [
                    'myFilter' => [
                        'name' => 'MyFilter',
                    ],
                ],
            ]
        ]
    ]
];
```
В остальном конфигурация `fallbackWriter` аналогична конфигурации простых врайтеров.
Только отсутствует параметр `priority`.