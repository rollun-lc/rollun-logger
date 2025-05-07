<?php

namespace rollun\logger\Processor;

use Psr\Log\LogLevel;

class ChangeLevel implements ProcessorInterface
{
    private const PRIORITIES = [
        0 => LogLevel::EMERGENCY,
        1 => LogLevel::ALERT,
        2 => LogLevel::CRITICAL,
        3 => LogLevel::ERROR,
        4 => LogLevel::WARNING,
        5 => LogLevel::NOTICE,
        6 => LogLevel::INFO,
        7 => LogLevel::DEBUG,
    ];

    /** @var string */
    private $from;

    /** @var string */
    private $to;

    public function __construct(array $options)
    {
        if (!isset($options['from']) || !in_array($options['from'], self::PRIORITIES)) {
            throw new \InvalidArgumentException("Invalid 'from' option");
        }

        if (!isset($options['to']) || !in_array($options['to'], self::PRIORITIES)) {
            throw new \InvalidArgumentException("Invalid 'to' option");
        }

        $this->from = $options['from'];
        $this->to = $options['to'];
    }

    public function process(array $event): array
    {
        $level = $event['level'] ?? null;

        if ($level !== $this->from) {
            return $event;
        }

        $event['level'] = $this->to;
        $event['priority'] = array_search($this->to, self::PRIORITIES);

        return $event;
    }
}
