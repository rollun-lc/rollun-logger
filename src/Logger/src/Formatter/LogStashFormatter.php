<?php

namespace rollun\logger\Formatter;

use DateTime;
use InvalidArgumentException;
use rollun\logger\Services\JsonTruncator;
use rollun\logger\Services\JsonTruncatorInterface;
use RuntimeException;

class LogStashFormatter implements FormatterInterface
{
    public const DEFAULT_MAX_SIZE = 1500;

    // key in 'context' field of log which can be used to pass index_name
    public const INDEX_NAME_KEY = 'es_index_name';

    public function __construct(private string $index, private ?array $columnMap = null, private ?JsonTruncatorInterface $jsonTruncator = null)
    {
        $this->jsonTruncator = is_null($jsonTruncator) ? new JsonTruncator(self::DEFAULT_MAX_SIZE) : $jsonTruncator;
    }

    /**
     * @inheritDoc
     */
    public function format($event)
    {
        $event['timestamp'] = $event['timestamp'] instanceof DateTime ? $event['timestamp']->format('Y-m-d\TH:i:s.u\Z') : $event['timestamp'];
        // If index_name is set in context - use it
        if (!empty($event['context'][static::INDEX_NAME_KEY])) {
            $event['_index_name'] = $event['context'][static::INDEX_NAME_KEY];
            unset($event['context'][static::INDEX_NAME_KEY]);
        } else {
            $event['_index_name'] = $this->index;
        }
        try {
            $event['context'] = json_decode($this->jsonTruncator->truncate(json_encode($event['context'])));
        } catch (InvalidArgumentException) {
            $event['context'] = '{}';
        }
        $dataToInsert = $this->columnMap ? $this->mapEventIntoColumn($event, $this->columnMap) : $event;
        return json_encode($dataToInsert);
    }

    /**
     * Map event into column using the $columnMap array
     *
     * @param  array $event
     * @param  array $columnMap
     * @return array
     */
    protected function mapEventIntoColumn(array $event, array $columnMap = null)
    {
        if (empty($event)) {
            return [];
        }

        $data = [];
        foreach ($event as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $subValue) {
                    if (is_array($columnMap[$name]) && isset($columnMap[$name][$key])) {
                        if (is_scalar($subValue)) {
                            $data[$columnMap[$name][$key]] = $subValue;
                            continue;
                        }

                        $data[$columnMap[$name][$key]] = var_export($subValue, true);
                    }
                }
            } elseif (isset($columnMap[$name])) {
                $data[$columnMap[$name]] = $value;
            }
        }
        return $data;
    }

    /**
     * Get the format specifier for DateTime objects
     *
     * @return string
     */
    public function getDateTimeFormat(): string
    {
        return DateTime::ISO8601;
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
