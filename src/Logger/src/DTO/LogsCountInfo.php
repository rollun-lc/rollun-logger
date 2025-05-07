<?php

namespace rollun\logger\DTO;

class LogsCountInfo
{
    /**
     * @param int $timeLimit Time in seconds
     */
    public function __construct(
        private int $timeLimit,
        private int $countLimit,
        private string $operator,
        private int $currentCount
    ) {}

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
