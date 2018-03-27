<?php


namespace rollun\logger\Writer;


use Traversable;
use Zend\Http\Client;
use Zend\Log\Writer\AbstractWriter;
use Zend\Uri\Http as HttpUri;

/**
 * Class Http
 * @package rollun\logger\Writer
 */
class Http extends AbstractWriter
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
    protected $uri;

    /**
     * HttpWriter constructor.
     * @param $client
     * @param string|HttpUri $uri
     * @param array $options
     */
    public function __construct($client, $uri = null, array $options = [])
    {
        if ($client instanceof Traversable) {
            $client = iterator_to_array($client);
        }
        if (is_array($client)) {
            parent::__construct($client);
            $options = isset($client["options"]) ? $client["options"] : [];
            $uri = isset($client["uri"]) ? $client["uri"] : null;
            $client = isset($client["client"]) ? $client["client"] : null;
            $client = is_string($client) ? new $client() : $client;
        }

        if (!$client instanceof Client) {
            throw new \InvalidArgumentException('You must pass a valid Zend\Http\Client');
        }
        $this->client = $client;
        $this->options = $options;
        $uri = isset($uri) ? $uri : $client->getUri();
        $this->uri = new HttpUri($uri);
    }

    /**
     * @param $uri
     * @param array $options
     * @return Client
     */
    private function initHttpClient($uri, $options = [])
    {
        $httpClient = clone $this->client;
        $httpClient->setUri($uri);
        $httpClient->setOptions($options);
        $headers['Content-Type'] = 'application/octet-stream';
        $headers['APP_ENV'] = constant('APP_ENV');
        $httpClient->setHeaders($headers);
        if (isset($this->login) && isset($this->password)) {
            $httpClient->setAuth($this->login, $this->password);
        }
        $httpClient->setMethod("POST");
        return $httpClient;
    }

    /**
     * @param array $event
     * @throws \Exception
     */
    public function write(array $event)
    {
        if(!isset($this->uri) || !$this->uri->isValid()) {
            return;
        }
        parent::write($event);
    }


    /**
     * Write a message to the log
     *
     * @param array $event log data event
     * @return void
     */
    protected function doWrite(array $event)
    {
        $client = $this->initHttpClient($this->uri, $this->options);
        $serialisedData = serialize($event);
        $rawData = base64_encode($serialisedData);
        $client->setRawBody($rawData);
        $response = $client->send();
        if ($response->isServerError()) {
            throw new \RuntimeException(sprintf(
                "Error with status %s by send event to %s, with message: %s",
                $response->getStatusCode(),
                $this->uri,
                $response->getReasonPhrase()
            ));
        }
    }
}