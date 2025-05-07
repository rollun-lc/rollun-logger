<?php

namespace rollun\logger\Processor;

use rollun\logger\DTO\LogsCountInfo;

class ProcessorWithCount implements ProcessorInterface
{
    public const KEY = LogsCountInfo::class;

    public function process(array $event): array
    {
        if (!isset($event['context'][self::KEY])) {
            return $event;
        }

        $logsCountInfo = $event['context'][self::KEY];

        $event['context']['count_checker'] = [
            'current_count' => $logsCountInfo->getCurrentCount(),
            'operator' => $logsCountInfo->getOperator(),
            'count_limit' => $logsCountInfo->getCountLimit(),
            'time_limit' => $logsCountInfo->getTimeLimit(),
        ];

        return $event;
    }
}
