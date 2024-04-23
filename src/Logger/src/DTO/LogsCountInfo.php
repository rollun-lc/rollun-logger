<?php

namespace rollun\logger\DTO;

class LogsCountInfo
{
    /**
     * Time in seconds
     * @var int
     */
    private $timeLimit;

    /** @var int */
    private $countLimit;

    /** @var string */
    private $operator;

    /** @var int */
    private $currentCount;

    public function __construct(int $timeLimit, int $countLimit, string $operator, int $currentCount)
    {
        $this->timeLimit = $timeLimit;
        $this->countLimit = $countLimit;
        $this->operator = $operator;
        $this->currentCount = $currentCount;
    }

    public function getTimeLimit(): int
    {
        return $this->timeLimit;
    }

    public function getCountLimit(): int
    {
        return $this->countLimit;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }
}