<?php


namespace rollun\logger\Writer;

use InvalidArgumentException;
use Jaeger\Transport\TUDPTransport;
use RuntimeException;
use Traversable;
use Zend\Log\Writer\AbstractWriter;

class Udp extends AbstractWriter
{
    const MAX_ATTEMPTS = 3;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var TUDPTransport
     */
    protected $client;

    public function __construct($client, array $options = [])
    {
        if ($client instanceof Traversable) {
            $client = iterator_to_array($client);
        }

        if (is_array($client)) {
            /*if(!isset($client['options']['_index'])) {
                throw new InvalidArgumentException('You must pass a _index');
            }*/

            $options = array_merge(
                [
                    'ignore_error' => false,     // Suppress writer exceptions
                    'auto_flash' => true,     // Suppress writer exceptions
                ], $client['options'] ?? []
            );

            if (!isset($client['formatter'])) {
                throw new InvalidArgumentException('You must pass formatter');
            }

            $client['options'] = $options;
            parent::__construct($client);
            $client = $client['client'] ?? null;
            $client = is_array($client) ? new TUDPTransport($client['host'], $client['port']) : $client;
        }

        if (!$client instanceof TUDPTransport) {
            throw new InvalidArgumentException('You must pass a valid \Jaeger\Transport\TUDPTransport');
        }

        $this->client = $client;
        $this->options = $options;
        $this->client->open();
    }

    public function __destruct()
    {
        $this->client->close();
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
        $message = $this->formatter->format($event);
        $this->client->write($message);

        $this->flushMessage();
    }

    private function flushMessage()
    {
        for ($attempts = 0; $attempts <= self::MAX_ATTEMPTS; $attempts++) {
            try {
                if ($this->options['auto_flash']) {
                    $this->client->flush();
                    return;
                }
            } catch (\Throwable $exception) {
            }
        }
        if (!$this->options['ignore_error']) {
            throw new RuntimeException('Error sending messages to Udp', 0);
        }
    }
}
