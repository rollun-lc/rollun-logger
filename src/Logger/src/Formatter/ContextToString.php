<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger\Formatter;


class ContextToString extends Db
{
    const DEFAULT_FORMAT = '%id% %timestamp% %level% %message% %context%';

    public function format($event)
    {
        $event = parent::format($event);
        $event['context'] = json_encode($event['context']);
        return $event;
    }
}
