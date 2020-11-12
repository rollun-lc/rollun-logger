<?php


namespace rollun\logger\Formatter;


use DateTime;
use RuntimeException;

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
	public function format(array $event)
	{
		$event['_index'] = $this->index;
		$event['_type'] = $this->type;
        $event['timestamp'] = $event['timestamp'] instanceof DateTime ? $event['timestamp']->format('c') : $event['timestamp'];
        return $event;
	}

	/**
	 * Get the format specifier for DateTime objects
	 *
	 * @return string
	 */
	public function getDateTimeFormat(): string
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
	public function setDateTimeFormat(string $dateTimeFormat): FormatterInterface
	{
		throw new RuntimeException('Operation set format unavailable.');
	}
}