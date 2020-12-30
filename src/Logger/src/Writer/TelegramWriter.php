<?php


namespace rollun\logger\Writer;


use rollun\dic\InsideConstruct;
use rollun\utils\TelegramClient;
use Traversable;

/**
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
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }
        if (is_array($options)) {
            parent::__construct($options);
            $chatIds = isset($options["chat_ids"]) ? $options["chat_ids"] : null;
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