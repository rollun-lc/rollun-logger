<?php
declare(strict_types=1);

namespace rollun\logger\Formatter;

use Zend\Log\Formatter\Db;

/**
 * Class Metric
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class Metric extends Db
{
    /**
     * @inheritDoc
     */
    public function format($event)
    {
        $event = [
            'value'     => isset($event['context']['value']) ? $event['context']['value'] : null,
            'timestamp' => time(),
        ];

        return $event;
    }
}
