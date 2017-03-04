<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.01.17
 * Time: 10:24
 */

namespace rollun\logger\LogWriter;

interface LogWriterInterface
{
    const DEFAULT_LOG_WRITER_SERVICE = LogWriterInterface::class;

    public function logWrite($id, $level, $message);
}
