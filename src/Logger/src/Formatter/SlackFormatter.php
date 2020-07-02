<?php
declare(strict_types=1);

namespace rollun\logger\Formatter;

use Zend\Log\Formatter\Db as Base;

/**
 * Class SlackFormatter
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class SlackFormatter extends Base
{
    /**
     * @inheritDoc
     */
    public function format($event)
    {
        $event['slackMessage'] = '*' . strtoupper($event['level']) . '* ' . $event['message'];
        if (!empty($event['context'])) {
            $event['slackMessage'] .= ' `' . json_encode($event['context']) . '`';
        }

        return $event;
    }
}
