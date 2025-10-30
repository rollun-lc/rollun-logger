# Миграция на Symfony Cache (замена Laminas Cache)

## Что изменилось

В новой версии библиотека мигрировала с `laminas/laminas-cache` на `symfony/cache`. Это позволяет:
- Использовать современную версию PSR Cache (PSR-16)
- Избавиться от зависимости на устаревший `psr/cache: 1.0`
- Устанавливать актуальные кеш-библиотеки в ваших сервисах без конфликтов

## Breaking Changes

### 1. Изменен интерфейс в `CountPerTime`

**Было:**
```php
use Laminas\Cache\Storage\StorageInterface;

class CountPerTime implements ProcessorInterface
{
    public function __construct(
        private StorageInterface $storage,
        private string $key,
        array $options = null
    ) {
        // ...
    }
}
```

**Стало:**
```php
use Psr\SimpleCache\CacheInterface;

class CountPerTime implements ProcessorInterface
{
    public function __construct(
        private CacheInterface $storage,
        private string $key,
        array $options = null
    ) {
        // ...
    }
}
```

### 2. Изменена реализация `RedisStorageFactory`

Фабрика теперь возвращает `Psr\SimpleCache\CacheInterface` (Symfony Cache) вместо `Laminas\Cache\Storage\StorageInterface`.

### 3. Удалены зависимости

Удалены следующие пакеты:
- `laminas/laminas-cache`
- `laminas/laminas-cache-storage-adapter-filesystem`
- `laminas/laminas-cache-storage-adapter-redis`

Добавлены:
- `symfony/cache: ^6.0|^7.0`
- `laminas/laminas-validator: ^2.14` (отсутствовала явная зависимость)

## Кто затронут изменениями?

### ✅ НЕ затронуты (большинство случаев)

Вы **НЕ пострадаете**, если:

1. **Используете стандартную конфигурацию** через переменные окружения:
   ```bash
   LOGS_REDIS_HOST=localhost
   LOGS_REDIS_PORT=6379
   ```
   → Все работает автоматически без изменений в коде

2. **Используете Dependency Injection** для создания `CountPerTime`:
   ```php
   'log_processors' => [
       'factories' => [
           CountPerTime::class => CountPerTimeFactory::class,
       ],
   ],
   ```
   → Фабрика автоматически получит новый кеш-адаптер

3. **Используете `ConfigProvider` библиотеки** без переопределений:
   ```php
   return [
       \rollun\logger\ConfigProvider::class,
   ];
   ```
   → Конфигурация обновится автоматически

### ⚠️ Требуют изменений

Вам **нужно обновить код**, если:

#### 1. Переопределяете `StorageForLogsCount` с Laminas Cache

**Было:**
```php
use Laminas\Cache\Service\StorageAdapterFactoryInterface;

return [
    'dependencies' => [
        'factories' => [
            'StorageForLogsCount' => function($container) {
                $factory = $container->get(StorageAdapterFactoryInterface::class);
                return $factory->create('redis', [
                    'ttl' => 86400,
                    'server' => [
                        'host' => 'localhost',
                        'port' => 6379,
                    ],
                ]);
            },
        ],
    ],
];
```

**Стало:**
```php
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

return [
    'dependencies' => [
        'factories' => [
            'StorageForLogsCount' => function($container) {
                $dsn = 'redis://localhost:6379';

                $redisAdapter = new RedisAdapter(
                    RedisAdapter::createConnection($dsn, ['timeout' => 1]),
                    'logs_',  // namespace
                    86400     // TTL
                );

                // Конвертируем PSR-6 в PSR-16
                return new Psr16Cache($redisAdapter);
            },
        ],
    ],
];
```

#### 2. Создаете `CountPerTime` вручную

**Было:**
```php
use Laminas\Cache\Storage\Adapter\Redis;
use rollun\logger\Processor\CountPerTime;

$cache = new Redis([
    'server' => [
        'host' => 'localhost',
        'port' => 6379,
    ],
]);

$processor = new CountPerTime($cache, 'myKey', [
    'time' => 3600,
    'count' => 100,
]);
```

**Стало:**
```php
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use rollun\logger\Processor\CountPerTime;

$redisAdapter = new RedisAdapter(
    RedisAdapter::createConnection('redis://localhost:6379'),
    'logs_',
    3600
);

$cache = new Psr16Cache($redisAdapter);

$processor = new CountPerTime($cache, 'myKey', [
    'time' => 3600,
    'count' => 100,
]);
```

#### 3. Используете type hints на старый интерфейс

**Было:**
```php
use Laminas\Cache\Storage\StorageInterface;

function processWithCache(StorageInterface $cache) {
    // ...
}
```

**Стало:**
```php
use Psr\SimpleCache\CacheInterface;

function processWithCache(CacheInterface $cache) {
    // ...
}
```

## Как обновиться

### Шаг 1: Обновите зависимости

```bash
composer update rollun-com/rollun-logger
```

### Шаг 2: Проверьте использование кеша

Найдите все места, где используется `StorageInterface` из Laminas:

```bash
grep -r "Laminas\\\\Cache\\\\Storage\\\\StorageInterface" src/
grep -r "StorageForLogsCount" config/
```

### Шаг 3: Обновите переопределения (если есть)

Если вы переопределяете `StorageForLogsCount` или создаете `CountPerTime` вручную, обновите код согласно примерам выше.

### Шаг 4: Запустите тесты

```bash
composer test
```

## API изменения в кеше

Если вы работаете с кешем напрямую, обратите внимание на изменения методов:

| Laminas Cache (было) | PSR-16 (стало) |
|----------------------|----------------|
| `$cache->getItem($key)` | `$cache->get($key)` |
| `$cache->setItem($key, $value)` | `$cache->set($key, $value)` |
| `$cache->hasItem($key)` | `$cache->has($key)` |
| `$cache->removeItem($key)` | `$cache->delete($key)` |
| `$cache->getItems($keys)` | `$cache->getMultiple($keys)` |
| `$cache->setItems($items)` | `$cache->setMultiple($items)` |

## Часто задаваемые вопросы

### Нужно ли менять переменные окружения?

Нет, переменные `LOGS_REDIS_HOST` и `LOGS_REDIS_PORT` остались без изменений.

### Будет ли работать старая конфигурация?

Если вы используете стандартную конфигурацию без переопределений `StorageForLogsCount` - да, все будет работать автоматически.

### Что делать с `laminas/laminas-filter`?

Эта зависимость осталась, так как нужна для `laminas/laminas-validator`. Удалять её не нужно.

### Как проверить, что миграция прошла успешно?

1. Запустите тесты: `make test` или `composer test`
2. Проверьте логи приложения - `CountPerTime` должен продолжать работать
3. Убедитесь, что подсчет логов в Redis работает корректно

## Дополнительные ресурсы

- [Symfony Cache Documentation](https://symfony.com/doc/current/components/cache.html)
- [PSR-16: Simple Cache Interface](https://www.php-fig.org/psr/psr-16/)
- [Конфигурация CountPerTime](filter-logs-by-count.md)
