# Файлы LifeCycleToken

## Описание
Чтобы отслеживать упавшие процессы, была введена возможность создавать файл при старте приложения и удалять его после завершения. Таким образом, если выполнение прервется, то в директории останется файл с информацией о процессе.

## Структура файла
Название файла - текущий `LifeCycleToken`.

Внутрь записываются такие данные: `parent_lifecycle_token` если есть, и значения `REMOTE_ADDR` и `REQUEST_URI` из `$_SERVER`.

## Использование
В `index.php` вашего приложения нужно выполнить такие действия:
1. В самом начале явно добавить создание `LifeCycleToken`:
    ```
    $lifeCycleToken = LifeCycleToken::createFromHeaders();
    
    // или для консоли:
    $lifeCycleToken = LifeCycleToken::createFromArgv();
    ```
2. На созданном `$lifeCycleToken` вызвать метод `createFile()` и передать в него путь к директории, в которой должны храниться создаваемые файлы:
   ```
   $lifeCycleToken->createFile('data/process-tracking/');
   ```
3. Добавить `$lifeCycleToken` в контейнер:
   ```
   $container->setService(LifeCycleToken::class, $lifeCycleToken);
   ```
4. В самом конце `index.php` вызывать удаление файла:
   ```
   $lifeCycleToken->removeFile();
   ```
   
## Пример index.php
```
<?php

use rollun\logger\LifeCycleToken;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;
use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$lifeCycleToken = LifeCycleToken::createFromHeaders();
$lifeCycleToken->createFile('data/process-tracking/');

/** @var ServiceManager $container */
$container = require 'config/container.php';

$container->setService(LifeCycleToken::class, $lifeCycleToken);

/** @var Application $app */
$app = $container->get(Application::class);
$factory = $container->get(MiddlewareFactory::class);

// Execute programmatic/declarative middleware pipeline and routing
// configuration statements
(require 'config/pipeline.php')($app, $factory, $container);
(require 'config/routes.php')($app, $factory, $container);

$app->run();

$lifeCycleToken->removeFile();
```