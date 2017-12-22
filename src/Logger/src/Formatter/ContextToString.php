<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Formatter;

use Zend\Log\Formatter\Db;

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
