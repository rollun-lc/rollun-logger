<?php


namespace rollun\logger\Formatter;


use DateTime;
use RuntimeException;

class LogStashFormatter implements FormatterInterface
{
    /**
     * @var string
     */
    private $index;
    /**
     * @var array
     */
    private $columnMap;

    public function __construct(string $index, array $columnMap = null)
    {
        $this->index = $index;
        $this->columnMap = $columnMap;
    }

    /**
     * @inheritDoc
     */
    public function format($event)
    {
        $event['timestamp'] = $event['timestamp'] instanceof DateTime ? $event['timestamp']->format('Y-m-d\TH:i:s.u\Z') : $event['timestamp'];
        $event['context'] = json_encode($event['context']);
        $event['_index_name'] = $this->index;
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