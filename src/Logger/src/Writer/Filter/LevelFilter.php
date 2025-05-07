<?php

namespace rollun\logger\Writer\Filter;

use rollun\logger\Filter\FilterInterface;

class LevelFilter implements FilterInterface
{
    /**
     * PriorityFilter constructor.
     * @param array $levels
     */
    public function __construct(private $levels = []) {}

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array $event event data
     * @return bool accepted?
     */
    public function filter(array $event): bool
    {
        return in_array($event["level"], $this->levels);
    }
}
