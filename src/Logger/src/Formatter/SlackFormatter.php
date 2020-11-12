<?php
declare(strict_types=1);

namespace rollun\logger\Formatter;

/**
 * Class SlackFormatter
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class SlackFormatter extends Db
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
