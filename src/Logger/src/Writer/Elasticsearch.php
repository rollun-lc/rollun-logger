<?php


namespace rollun\logger\Writer;


use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use rollun\logger\Formatter\Elasticsearch as ElasticsearchFormatter;

/**
 * Class Elasticsearch
 * @package rollun\logger\Writer
 */
class Elasticsearch extends AbstractWriter
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
	 * HttpWriter constructor.
	 * @param $client
	 * @param array $options
	 */
	public function __construct($client, array $options = [])
	{
		if ($client instanceof Traversable) {
			$client = iterator_to_array($client);
		}

		if (is_array($client)) {
			$options = array_merge(
				[
					'index' => 'rollun_log', // Elastic index name
					'type' => '_doc',    // Elastic document type
					'ignore_error' => false,     // Suppress Elasticsearch exceptions
				], $client['options'] ?? []
			);
			if (!isset($client['formatter'])) {
				$client['formatter'] = new ElasticsearchFormatter($options['index'], $options['type']);

			}

			$client['options'] = $options;
			parent::__construct($client);
			$client = $client['client'] ?? null;
			$client = is_array($client) ? ClientBuilder::create()->setHosts($client)->build() : $client;
		}

		if (!$client instanceof Client) {
			throw new InvalidArgumentException('You must pass a valid Elasticsearch\Client');
		}

		$this->client = $client;
		$this->options = $options;
	}

	/**
	 * Write a message to the log
	 *
	 * @param array $event log data event
	 * @return void
	 */
	protected function doWrite(array $event)
	{
		try {
			$event = $this->formatter->format($event);
			$params = [
				'body' => []
			];

			$params['body'][] = [
				'index' => [
					'_index' => $event['_index'],
					'_type' => $event['_type'],
				],
			];
			unset($event['_index'], $event['_type']);

			$params['body'][] = $event;

			$responses = $this->client->bulk($params);

			if ($responses['errors'] === true) {
				throw new RuntimeException('Elasticsearch returned error for one of the records');
			}
		} catch (\Throwable $exception) {
			if (!$this->options['ignore_error']) {
				throw new RuntimeException('Error sending messages to Elasticsearch', 0, $exception);
			}
		}
	}
}
