<?php


namespace rollun\logger\Writer;


use rollun\dic\InsideConstruct;
use rollun\utils\TelegramClient;
use Traversable;

/**
 * Райтер был отключен, потому что на данный момент он не используется в наших сервисах,
 * но требует подключение библиотеки rollun-utils, которую мы решили убрать из зависимостей логгера.
 * Чтобы снова включить этот райтер нужно или
 * 1. вернуть зависимость rollun-utils (нежелательно),
 * 2. или перенести этот класс в другую библиотеку.
 * Class TelegramWriter
 * @package rollun\logger\Writer
 */
class TelegramWriter extends AbstractWriter
{
    protected $chatIds = [];

    /** @var TelegramClient */
    protected $client;

    /**
     * TelegramWriter constructor.
     * @param $options
     * @param TelegramClient|null $client
     * @param $chatIds
     * @throws \ReflectionException
     */
    public function __construct($options, TelegramClient $client = null, $chatIds = null)
    {
        throw new \Exception("Writer is disabled because it has no usages and depends on rollun-utils library, which will be removed from logger dependencies");

        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }
        if (is_array($options)) {
            parent::__construct($options);
            $chatIds = $options["chat_ids"] ?? null;
        }
        InsideConstruct::setConstructParams(["client" => TelegramClient::class]);
        $this->chatIds = $chatIds ?? [];
    }

    /**
     * Write a message to the log
     *
     * @param array $event log data event
     * @return void
     */
    protected function doWrite(array $event)
    {
        $message = $this->formatter->format($event);
        foreach ($this->chatIds as $id) {
            $this->client->write($message, $id);
        }
    }
}