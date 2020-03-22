<?php


namespace rollun\logger\Writer;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use InvalidArgumentException;
use Jaeger\Transport\TUDPTransport;
use rollun\logger\Formatter\Elasticsearch as ElasticsearchFormatter;
use RuntimeException;
use Traversable;
use Zend\Log\Writer\AbstractWriter;

class Udp extends AbstractWriter
{

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
        try {
            $message = $this->formatter->format($event);

            $this->client->write($message);
            if ($this->options['auto_flash']) {
                $this->client->flush();
            }

        } catch (\Throwable $exception) {
            if (!$this->options['ignore_error']) {
                throw new RuntimeException('Error sending messages to Udp', 0, $exception);
            }
        }
    }

    public function flash()
    {
        $this->client->flush();
    }
}