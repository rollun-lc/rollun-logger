<?php


namespace rollun\logger\Formatter;


use DateTime;
use Zend\Log\Formatter\FormatterInterface;

class Elasticsearch implements FormatterInterface
{
	/**
	 * @var string Elasticsearch index name
	 */
	protected $index;
	/**
	 * @var string Elasticsearch record type
	 */
	protected $type;

	/**
	 * @param string $index Elasticsearch index name
	 * @param string $type Elasticsearch record type
	 */
	public function __construct(string $index, string $type)
	{
		// Elasticsearch requires an ISO 8601 format date with optional millisecond precision.
		$this->index = $index;
		$this->type = $type;
	}

	/**
	 * Formats data into a single line to be written by the writer.
	 *
	 * @param array $event event data
	 * @return string|array Either a formatted line to write to the log, or the
	 *     updated event information to provide to the writer.
	 */
	public function format($event)
	{
		$event['_index'] = $this->index;
		$event['_type'] = $this->type;
		return $event;
	}

	/**
	 * Get the format specifier for DateTime objects
	 *
	 * @return string
	 */
	public function getDateTimeFormat()
	{
		return DateTime::ATOM;
	}

	/**
	 * Set the format specifier for DateTime objects
	 *
	 * @see http://php.net/manual/en/function.date.php
	 * @param string $dateTimeFormat DateTime format
	 * @return FormatterInterface
	 */
	public function setDateTimeFormat($dateTimeFormat)
	{
		throw new \RuntimeException('Operation set format unavailable.');
	}
}