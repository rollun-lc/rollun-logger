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
        $event = [
            'value' => isset($event['context']['value']) ? $event['context']['value'] : null,
            'timestamp' => (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp()
        ];

        return $event;
    }
}
