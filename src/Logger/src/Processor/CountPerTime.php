<?php


namespace rollun\logger\Processor;


use Laminas\Cache\Storage\StorageInterface;
use rollun\logger\DTO\LogsCountInfo;

class CountPerTime implements ProcessorInterface
{
    public const KEY_TIME = 'time';

    public const KEY_COUNT = 'count';

    public const KEY_OPERATOR = 'operator';

    public const KEY_ON_TRUE = 'onTrue';

    public const KEY_ON_FALSE = 'onFalse';

    private StorageInterface $storage;

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

        $isTrue = $this->compareByOperator($count);

        $processorsToRun = $isTrue ? $this->onTrue : $this->onFalse;

        $event['context'][ProcessorWithCount::KEY] = $this->buildCountInfo($count);

        foreach ($processorsToRun as $processor) {
            $event = $processor->process($event);
        }

        unset($event['context'][ProcessorWithCount::KEY]);

        return $event;
    }

    protected function buildCountInfo(int $count): LogsCountInfo
    {
        return new LogsCountInfo(
            $this->timeLimit,
            $this->countLimit,
            $this->operator,
            $count
        );
    }

    protected function compareByOperator(int $count): bool
    {
        return version_compare($count, $this->countLimit, $this->operator);
    }

    protected function getTimeKey(): string
    {
        return "$this->key:CountPerTime:timestamp";
    }

    protected function getCountKey(): string
    {
        return "$this->key:CountPerTime:count";
    }
}
