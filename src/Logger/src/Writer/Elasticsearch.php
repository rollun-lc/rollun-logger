<?php


namespace rollun\logger\Writer;


use DateTime;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Traversable;

/**
 * Class Elasticsearch
 * @package rollun\logger\Writer
 */
class Elasticsearch extends AbstractWriter
{
    private const INDEX_MASK = '{index_name}_logs-{date}';
    private const DEFAULT_TYPE = '_doc';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string Elasticsearch index name
     */
    protected $indexName;

    /**
     * @var string Elasticsearch record type
     */
    protected $type;

    /**
     * HttpWriter constructor.
     * @param Client|array $client
     * @param string|null $indexName elasticSearch index_name
     * @param string|null $type elasticsearch type
     */
    public function __construct($client, string $indexName = null, string $type = null)
    {
        if ($client instanceof Traversable) {
            $client = iterator_to_array($client);
        }

        if (is_array($client)) {
            parent::__construct($client);
            $this->validateConfiguration($client);

            $indexName = $client['indexName'] ?? null;
            $type = $client['type'] ?? null;
            $client = $this->createClient($client);
        }

        if (!$client instanceof Client) {
            throw new InvalidArgumentException('You must pass a valid Elasticsearch\Client');
        }

        if ($indexName === null) {
            throw new InvalidArgumentException("IndexName is required.");
        }

        $this->client = $client;
        $this->indexName = $indexName;
        $this->type = $type === null ? self::DEFAULT_TYPE : $type;
    }

    /**
     * Checks configuration for the presence and format of fields
     *
     * @param array $config
     */
    private function validateConfiguration(array $config)
    {
        $type = $config['type'] ?? null;

        if (!isset($config['indexName']) || !is_string($config['indexName'])) {
            throw new InvalidArgumentException("You must pass IndexName as a string.");
        }

        if (isset($config['type']) && !is_string($type)) {
            throw new InvalidArgumentException("Type must be a string.");
        }

        if (!isset($config['client'])) {
            throw new InvalidArgumentException('Client must be specified in options for elasticsearch writer.');
        }
    }

    /**
     * Create a client from configuration
     *
     * @param $client
     * @return Client
     */
    private function createClient($client): Client
    {
        if ($client['client'] instanceof Client) {
            return $client['client'];
        }

        if(is_array($client['client'])) {
            $clientConfig = $client['client'] ?? [];
            if (!isset($clientConfig['hosts'])) {
                throw new InvalidArgumentException('Hosts property must be set for elasticsearch client.');
            }
            return ClientBuilder::create()->setHosts($client['client']['hosts'])->build();
        }

        throw new InvalidArgumentException('You must pass a valid Elasticsearch\Client or array with configuration to elasticsearch writer options.');
    }

    /**
     * Write a message to elasticsearch
     *
     * @param array $event log data event
     * @return void
     * @throws Exception
     */
    protected function doWrite(array $event)
    {
        // Change timestamp field name
        if (isset($event['timestamp'])) {
            $event['@timestamp'] = $event['timestamp'] instanceof DateTime ?
                $event['timestamp']->format('c') :
                $event['timestamp'];
            unset($event['timestamp']);
        }

        $index = $this->createIndexByMask(self::INDEX_MASK, $event);

        // Formatter can return an event as a string, so don't call it before
        if ($this->hasFormatter()) {
            $event = $this->getFormatter()->format($event);
        }

        try {
            // Send event to elastic
            $params = [
                'index' => $index,
                'type' => $this->type,
                'body' => $event
            ];
            $this->client->index($params);
        } catch (Throwable $exception) {
            throw new RuntimeException('Error sending messages to Elasticsearch', 0, $exception);
        }
    }

    /**
     * Inserts index_name and date into index mask
     *
     * @param string $mask
     * @param $event
     * @return string
     * @throws Exception
     */
    private function createIndexByMask(string $mask, $event): string
    {
        $timestamp = null;
        if (!isset($event['timestamp'])) {
            $timestamp = new DateTime();
        } elseif ($event['timestamp'] instanceof DateTime) {
            $timestamp = $event['timestamp'];
        }

        $index = strtr($mask, [
                '{index_name}' => $this->indexName,
                '{date}' => $timestamp ? $timestamp->format('Y-m-d')
                    : (new DateTime($event['timestamp']))->format('Y-m-d')
            ]
        );

        return $index;
    }
}
