<?php


namespace rollun\logger\Processor;


use Zend\Cache\Storage\StorageInterface;

class CountPerTime implements ProcessorInterface
{
    public const KEY_TIME = 'time';

    public const KEY_COUNT = 'count';

    public const KEY_OPERATOR = 'operator';

    public const KEY_ON_TRUE = 'onTrue';

    public const KEY_ON_FALSE = 'onFalse';

    /** @var StorageInterface */
    private $storage;

    /**
     * Key to save in storage
     * @var string
     */
    private $key;

    /**
     * Time in seconds
     * @var int
     */
    private $timeLimit = 3600;

    /** @var int */
    private $countLimit = 100;

    /** @var string */
    private $operator = '<';

    /** @var ProcessorInterface[] */
    private $onTrue = [];

    /** @var ProcessorInterface[]  */
    private $onFalse = [];

    public function __construct(StorageInterface $storage, string $key, array $options = null)
    {
        $this->storage = $storage;
        $this->key = $key;

        if (isset($options[self::KEY_TIME])) {
            $this->timeLimit = $options[self::KEY_TIME];
        }

        if (isset($options[self::KEY_COUNT])) {
            $this->countLimit = $options[self::KEY_COUNT];
        }

        if (isset($options[self::KEY_OPERATOR])) {
            $this->operator = $options[self::KEY_OPERATOR];
        }

        if (isset($options[self::KEY_ON_TRUE])) {
            $this->onTrue = $options[self::KEY_ON_TRUE];
        }

        if (isset($options[self::KEY_ON_FALSE])) {
            $this->onFalse = $options[self::KEY_ON_FALSE];
        }
    }

    public function process(array $event): array
    {
        $now = time();

        $timeKey = $this->getTimeKey();
        $countKey = $this->getCountKey();

        $lastTimestamp = $this->storage->getItem($timeKey);
        $count = $this->storage->getItem($countKey) ?? 0;

        if ($lastTimestamp < $now - $this->timeLimit) {
            $this->storage->setItem($timeKey, $now);
            $count = 0;
        }

        $count++;
        $this->storage->setItem($countKey, $count);

        $isTrue = version_compare($count, $this->countLimit, $this->operator);

        if ($isTrue) {
            foreach ($this->onTrue as $processor) {
                $event = $processor->process($event);
            }
        } else {
            $event['context']['count_checker'] = [
                'count' => $count,
                'count_limit' => $this->countLimit,
                'time_limit' => $this->timeLimit,
            ];
            foreach ($this->onFalse as $processor) {
                $event = $processor->process($event);
            }
        }

        return $event;
    }

    private function getTimeKey(): string
    {
        return "$this->key:CountPerTime:timestamp";
    }

    private function getCountKey(): string
    {
        return "$this->key:CountPerTime:count";
    }
}
