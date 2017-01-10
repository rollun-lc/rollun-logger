<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.01.17
 * Time: 10:24
 */

namespace zaboy\logger;

interface LogWriter
{
    public function logWrite($id, $level, $message);
}
