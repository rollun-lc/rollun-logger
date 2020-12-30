<?php


namespace rollun\logger\Writer\Filter;


use rollun\logger\Filter\FilterInterface;

class ContextFilter implements FilterInterface
{
    /**
     * @var callable
     */
    private $filter;

    /**
     * PriorityFilter constructor.
     * @param callable $filter
     */
    public function __construct(callable $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array $event event data
     * @return bool accepted?
     */
    public function filter(array $event): bool
    {
        return call_user_func($this->filter, $event);
    }
}