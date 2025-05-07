<?php


namespace rollun\logger\Writer;


use InvalidArgumentException;
use RuntimeException;
use Traversable;
use Laminas\Http\Client;
use Laminas\Uri\Http as HttpUri;

class HttpMetric extends AbstractWriter
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var HttpUri
     */
    protected $url;

    /**
     * HttpWriter constructor.
     * @param $client
     * @param string|HttpUri $url
     * @param array $options
     */
    public function __construct($client, $url = null, array $options = [])
    {
        if ($client instanceof Traversable) {
            $client = iterator_to_array($client);
        }

        if (is_array($client)) {
            parent::__construct($client);
            $options = $client['options'] ?? [];
            $url = $client['url'] ?? null;
            $client = $client['client'] ?? new Client();
        }

        if (!$client instanceof Client) {
            throw new InvalidArgumentException('You must pass a valid Laminas\Http\Client');
        }

        $this->client = $client;
        $this->options = $options;
        $url = $url ?? $client->getUri();
        $this->url = new HttpUri($url);
    }

    /**
     * @param $url
     * @param array $options
     * @return Client
     */
    private function initHttpClient($url, $options = []): Client
    {
        $httpClient = clone $this->client;
        $httpClient->setUri($url);
        $httpClient->setOptions($options);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $httpClient->setHeaders($headers);

        $httpClient->setMethod('POST');

        return $httpClient;
    }

    public function write(array $event): void
    {
        if (!isset($this->url) || !$this->url->isValid() || !$this->isValid($event)) {
            return;
        }

        parent::write($event);
    }

    protected function isValid(array $event): bool
    {
        if (!isset($event['context']['metricId']) || !isset($event['context']['value'])) {
            return false;
        }

        return true;
    }

    protected function doWrite(array $event): void
    {
        $url = $this->url . '/' . $event['context']['metricId'];

        // call formatter
        if ($this->hasFormatter()) {
            $event = $this->getFormatter()->format($event);
        }

        // array to json
        $data = json_encode($event);

        $client = $this->initHttpClient($url, $this->options);
        $client->setRawBody($data);

        $response = $client->send();

        if ($response->isServerError()) {
            throw new RuntimeException(
                sprintf(
                    'Error with status %s by send event to %s, with message: %s',
                    $response->getStatusCode(),
                    $url,
                    $response->getReasonPhrase()
                )
            );
        }
    }
}