#rollun-logger

Logger класс реализует интерфейс `Psr\Log\LoggerInterface`

Для запуска тестов настройте доступ к БД в config\autoload\logger.development.test.global

и создайте таблицу в БД скриптом create_table_logs

# Lifecycle token 
Для использования lifecycle token необходимо добавить в index.php
```php

    $lifeCycleToke = \rollun\logger\LifeCycleToken::generateToken();
    if(apache_request_headers() && array_key_exists("LifeCycleToken", apache_request_headers())) {
        $lifeCycleToke->unserialize(apache_request_headers()["LifeCycleToken"]);
    }
    /** use container method to set service.*/
    $container->setService(\rollun\logger\LifeCycleToken::class, $lifeCycleToke)

```