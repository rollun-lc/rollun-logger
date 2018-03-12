#rollun-logger

Logger класс реализует интерфейс `Psr\Log\LoggerInterface`

Для запуска тестов настройте доступ к БД в config\autoload\logger.development.test.global

и создайте таблицу в БД скриптом create_table_logs


#Transition Version
Переходная версия, для продолжения использования старого логера, но перехода к использованию нового (на основе zend-logger).  
Что бы получить новый логер, нужно взять из контейнера сервис с именем `\Psr\Log\LoggerInterface::class`.  
Для старого логера, используйте `\rollun\logger\Logger`. Так же можно получать сервис через `new Logger();`
> Этот способ обьявлен устаревшим, и не рекомендуется к использованию в дальнейшем.
