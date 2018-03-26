<?php


namespace rollun\logger\Writer;


use Zend\Http\Client;
use Zend\Log\Writer\AbstractWriter;

class Http extends AbstractWriter
{

    /**
     * @var array
     */
    protected $options;

    /**
     * HttpWriter constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->options = isset($options["http_options"]) ? $options["http_options"] : [];
    }

    /**
     * @param $uri
     * @param array $options
     * @return Client
     */
    protected function initHttpClient($uri, $options = [])
    {
        $httpClient = new Client();
        $httpClient->setUri($uri);
        $httpClient->setOptions($options);
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['APP_ENV'] = constant('APP_ENV');
        $httpClient->setHeaders($headers);
        if (isset($this->login) && isset($this->password)) {
            $httpClient->setAuth($this->login, $this->password);
        }
        $httpClient->setMethod("POST");
        return $httpClient;
    }

    /**
     * Write a message to the log
     *
     * @param array $event log data event
     * @return void
     */
    protected function doWrite(array $event)
    {
        if (isset($event["uri"])) {
            $uri = $event["uri"];
            $client = $this->initHttpClient($uri, $this->options);
            $serialisedData = serialize($event);
            $rawData = base64_encode($serialisedData);
            $client->setRawBody($rawData);
            $response = $client->send();
            if ($response->isServerError()) {
                throw new \RuntimeException(sprintf(
                    "Error with status %s by send event to %s, with message: %s",
                    $response->getStatusCode(),
                    $uri,
                    $response->getReasonPhrase()
                ));
            }
        }
    }
}