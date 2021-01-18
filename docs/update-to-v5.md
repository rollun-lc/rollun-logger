# Изменения в версии 5
1. Убрана зависимость на пакеты "avz-cmf/zend-psr3-log" и "zendframework/zend-log". В главном это означает, что все классы,
которые раньше находились под неймспейсами `Zend\Log` переместились в неймспейс `rollun\logger`, или были удаленны.
Если вы не можете найти класс, который использовали, под новым неймспейсом, то зачастую его можно легко добавить, просто
поменяв его неймспейс и интерфейс на аналогичный из "rollun-logger". 
   
2. С версии 5.2.1 добавлена фабрика для LifeCycleToken, которая так же сразу добавлена в `rollun\logger\ConfigProvider`.
Так что больше нету необходимости писать в index.php следующие строки (И лучше их убрать)
```php
// Init lifecycle token
$lifeCycleToken = LifeCycleToken::generateToken();
if (LifeCycleToken::getAllHeaders() && array_key_exists("LifeCycleToken", LifeCycleToken::getAllHeaders())) {
$lifeCycleToken->unserialize(LifeCycleToken::getAllHeaders()["LifeCycleToken"]);
}
/** @var \Zend\ServiceManager\ServiceManager $container */
$container->setService(LifeCycleToken::class, $lifeCycleToken);
```

Все что делал этот код - создавал LifeCycleToken и добавлял в контейнер. Теперь код создания находиться в фабрике, а 
в контейнер добавляется через конфигурацию. Так что по идее это одно и то же.

Так же изменился сам механизм создания токена. Ибо согласно [RFC2616](https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2)
заголовки регистронезависимы, а так же в переменной [$_SERVER](https://www.php.net/manual/ru/reserved.variables.server.php)
(из которой берутся заголовки) все ключи находятся в верхнем регистре. Так что код ```array_key_exists("LifeCycleToken", LifeCycleToken::getAllHeaders())```
вообще не должен работать, так как тут регистрозависимое сравнение. Пометил этот метод на deprecated, вместо этого рекомендуется
создавать LifeCycleToken через метод createFromHeaders() (Добавлен в 5.2).

# Что делать для обновления

**1. Обновите зависимости в composer.json**

Для обновления логгера, нужно обновить почти все остальные библиотеки с префиксом
"rollun-com".

Рекомендую сначала обновить пакеты с минорными релизами. Измените в composer.json версии на те, которые не ниже чем указанно.
```
"rollun-com/rollun-datastore": "^6.6",
"rollun-com/rollun-permission": "^4.7",
"rollun-com/rollun-usps": "^2.4",
"rollun-com/rollun-walmart": "^1.2",
"rollun-com/rollun-parser": "^1.1",
```

и запустите обновление.

```bash
composer update "rollun-com/*" --with-dependencies
```

После чего обновить логгер и все библиотеки, которым, для поддержки логгера, потребовались мажорные релизы. Меняем 
composer.json на версии не ниже указанных.
```
"rollun-com/rollun-callback": "^6.0",
"rollun-com/rollun-openapi": "^3.0",
"rollun-com/rollun-logger": "^5.2",
"rollun-com/rollun-utils": "^5.0",
```

и обновляем.

```bash
composer update "rollun-com/*" --with-dependencies
```

Во всех мажорных релизах ничего не изменилось, помимо поддержки логгера пятой версии. Кроме "rollun-com/rollun-openapi",
в третьей версии генератор создает DTO классы с camelCase вместо snake_case, так что нужно быть внимательным ибо перегенерация
старых манифестов может сломать программу.

Если после обновления пакетов в директории `vendor` остался "avz-cmf/zend-psr3-log", то попробуйте запустить его удаление.

```bash
composer remove "avz-cmf/zend-psr3-log"
```

**2. Проследите чтобы в проекте нигде не использовались классы из пространства имен `Zend\Log`**
В большинстве случаев нужно поменять только конфигурацию в `config/autoload/logger.global.php`. Заменив все классы из
пространства имен `Zend\Log` на их аналоги из ```rollun\logger```. 

Но если по каким либо причинам вы используете классы из неймспейса `Zend\Log` где -ибо еще, то там их тоже нужно заменить.
Воспользуйтесь поиском phpStorm (ctrl + shift + f), чтобы проверить что строка `Zend\Log` нигде не встречается в вашем проекте.

**3. Удалите строки создания LifeCycleToken из index.php**
Из public/index.php удалите следующее
```php
// Init lifecycle token
$lifeCycleToken = LifeCycleToken::generateToken();
if (LifeCycleToken::getAllHeaders() && array_key_exists("LifeCycleToken", LifeCycleToken::getAllHeaders())) {
$lifeCycleToken->unserialize(LifeCycleToken::getAllHeaders()["LifeCycleToken"]);
}
/** @var \Zend\ServiceManager\ServiceManager $container */
$container->setService(LifeCycleToken::class, $lifeCycleToken);
```
Если у вас подключен `rollun\logger\ConfigProvider`, то больше ничего делать не нужно. Если нет, то добавьте в контейнер
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