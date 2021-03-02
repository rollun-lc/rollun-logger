<?php


namespace rollun\logger\Formatter;


use DateTime;
use InvalidArgumentException;
use rollun\logger\Services\JsonTruncator;
use RuntimeException;
use Zend\Log\Formatter\FormatterInterface;

class LogStashFormatter implements FormatterInterface
{
    // 350 is bytes reserved for service info like timestamp, index_name, e.t.c
    public const DEFAULT_MAX_SIZE = 32765 - 350;

    /**
     * @var string
     */
    private $index;

    /**
     * @var array
     */
    private $columnMap;

    /**
     * @var JsonTruncator
     */
    private $jsonTruncator;

    public function __construct(string $index, array $columnMap = null, ?JsonTruncator $jsonTruncator = null)
    {
        $this->index = $index;
        $this->columnMap = $columnMap;
        $this->jsonTruncator = is_null($jsonTruncator) ? new JsonTruncator(self::DEFAULT_MAX_SIZE) : $jsonTruncator;
    }

    /**
     * @inheritDoc
     */
    public function format($event)
    {
        $event['timestamp'] = $event['timestamp'] instanceof DateTime ? $event['timestamp']->format('Y-m-d\TH:i:s.u\Z') : $event['timestamp'];
        try {
            $event['context'] = $this->jsonTruncator
                ->withMaxSize($this->jsonTruncator->getMaxSize() - strlen($event['message'] ?? ''))
                ->truncate(json_encode($event['context']));
        } catch (InvalidArgumentException $e) {
            // We get here when too small value gets into withMaxSize(), which means the message is too large.
            $event['message'] = $this->jsonTruncator->truncate($event['message']);
            $event['context'] = '{}';
        }
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
    public function getDateTimeFormat()
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
    public function setDateTimeFormat($dateTimeFormat)
    {
        throw new RuntimeException('Operation set format unavailable.');
    }
}