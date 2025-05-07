<?php

declare(strict_types=1);

namespace rollun\logger\Formatter;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class Metric
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class Metric extends Db
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    public function format($event)
    {
        $newEvent = [
            'value' => $event['context']['value'] ?? null,
            'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp(),
        ];

        if (isset($event['context']['info'])) {
            $newEvent['info'] = $event['context']['info'];
        }

        return $newEvent;
    }
}
