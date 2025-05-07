<?php

declare(strict_types=1);

namespace rollun\logger\Writer;

use Laminas\Http\Client;
use Laminas\Http\Response;

/**
 * Class Slack
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class Slack extends AbstractWriter
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string|null
     */
    protected $channel = null;

    /**
     * @inheritDoc
     */
    public function __construct($options = null)
    {
        if (!empty($options['token'])) {
            $this->client = $this->createClient($options['token']);
        }

        if (!empty($options['channel'])) {
            $this->channel = $options['channel'];
        }

        parent::__construct($options);
    }

    /**
     * @inheritDoc
     */
    public function write(array $event): void
    {
        // call formatter
        if ($this->hasFormatter()) {
            $event = $this->getFormatter()->format($event);
        }

        if ($this->isValid($event)) {
            parent::write($event);
        }
    }

    /**
     * @param array $event
     *
     * @return bool
     */
    protected function isValid(array $event): bool
    {
        return !empty($this->client) && !empty($this->channel);
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
        $this->sendMessage($event['slackMessage']);
    }

    /**
     * @param string $message
     *
     * @return Response
     */
    protected function sendMessage(string $message): Response
    {
        $body = [
            'channel' => $this->channel,
            'text'    => $message,
        ];

        $this->client->setRawBody(json_encode($body));

        return $this->client->send();
    }

    /**
     * @param string $token
     *
     * @return Client
     */
    protected function createClient(string $token): Client
    {
        $httpClient = new Client();
        $httpClient->setUri('https://slack.com/api/chat.postMessage');

        $headers['Content-Type'] = 'application/json';
        $headers['Authorization'] = 'Bearer ' . $token;

        $httpClient->setHeaders($headers);

        $httpClient->setMethod('POST');

        return $httpClient;
    }
}
