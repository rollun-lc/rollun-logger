# Configuration

## Change defaults

Налаштування по замовчуванню знаходяться в файлі [ConfigProvider](/src/Logger/src/ConfigProvider.php)

### Stdout writer

По замовчуванню в нас налаштований врайтер, що записує логи в stdout:

```php
'stream_stdout' => [
    'name'    => Stream::class,
    'options' => [
        'stream'    => 'php://stdout',
        'formatter' => new FluentdFormatter()
    ],
],
```

Щоб додати обмеження на максимальний розмір логів, що записуються через цей врайтер, то потрібно в конфігурації свого
сервісу перевизначити `formatter`. [FluentdFormatter](/src/Logger/src/Formatter/FluentdFormatter.php) приймає 
опціональний параметр JsonTruncator, якому можна встановити максимальний розмір лога.

```php
return [
    'log' => [
        LoggerInterface::class => [
            'writers' => [
                'stream_stdout' => [
                    'options' => [
                        'formatter' => new FluentdFormatter(
                            // You can change maxSize to any you want, value in bytes
                            new JsonTruncator(1000)
                        )
                    ],
                ],    
            ]
        ]
    ]
]
```