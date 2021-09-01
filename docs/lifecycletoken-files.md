# Файлы LifeCycleToken

## Описание
Иногда процессы падают без логов, например, при перерасходе памяти процесс просто убивается OS. Чтобы отслеживать такие случаи, была сделана возможность писать файлы при старте процесса, которые должны удаляться при успешном завершении. Тогда если процесс прервется, в системе останется файл с информацией о нем.

## Структура файла
Название файла - текущий `LifeCycleToken`.

В сам файл записываются такие данные (если они есть):
* `parent_lifecycle_token`
* `$_SERVER['REMOTE_ADDR']`
* `$_SERVER['REQUEST_URI']`

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