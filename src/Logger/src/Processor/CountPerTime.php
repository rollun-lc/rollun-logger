<?php


namespace rollun\logger\Processor;


use rollun\logger\Filter\FilterInterface;

// todo
class CountPerTime implements FilterInterface
{
    public const KEY_TIME = 'time';

    public const KEY_COUNT = 'count';

    public const KEY_OPERATOR = 'operator';

    /**
     * Time in seconds
     * @var int
     */
    private $time = 3600;

    /** @var int */
    private $count = 100;

    /** @var string */
    private $operator = '>=';

    public function __construct(array $options = null)
    {
        if (isset($options[self::KEY_TIME])) {
            $this->time = $options[self::KEY_TIME];
        }

        if (isset($options[self::KEY_COUNT])) {
            $this->count = $options[self::KEY_COUNT];
        }

        if (isset($options[self::KEY_OPERATOR])) {
            $this->operator = $options[self::KEY_OPERATOR];
        }
    }

    public function filter(array $event)
    {
        return version_compare($event['priority'], $this->priority, $this->operator);
    }
}
