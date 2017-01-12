#Logger

Logger класс реализует интерфейс `Psr\Log\LoggerInterface`
> [Детальнее о нем тут](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)

Для записи лога используется обьект который имплементирует интерфейс `LogWriter`.

# FileLogWriter
Стандартная реализация интерфейса `LogWriter` для записи лога в файл.
> По умолчанию лог будет записать в `/dev/null`

`FileLogWriterFactory` - Создает инстанс `FileLogWriter` используя параметры из конфига.
Пример конфига:
```php
    "FileLogWriter" => [
        "file" => "log.txt",
        "delimiter" => ";",
        "endString" => "\n";
    ]
```
Где **file** - имя файла, **delimiter** - разделитель параметров, **endString** - символ завершения строки.

В случае если конфиг не найдет будет создан стандартный `FileLogWriter` который записывает лог в `/dev/null`.
