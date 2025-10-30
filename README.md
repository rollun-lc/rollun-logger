# rollun-logger

`rollun-logger` - логгер, который во многом основан на [zendframework/zend-log](https://github.com/zendframework/zend-log)
, но переписан для поддержки [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/).

`Elasticsearch` - writer и formatter позволяющий писать логи в elasticsearch.
[Пример конфигурации](config/autoload/local.test.php#68);

* [Документация](https://github.com/rollun-com/rollun-logger/blob/master/docs/index.md)
* [Формат логов](https://github.com/rollun-com/rollun-logger/blob/master/LOG_FORMAT.md)

## Migration Guides

* [Миграция на Symfony Cache](https://github.com/rollun-com/rollun-logger/blob/master/docs/migration-symfony-cache.md) - переход с Laminas Cache на Symfony Cache
