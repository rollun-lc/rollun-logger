<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.01.17
 * Time: 10:26
 */

namespace rollun\logger\LogWriter;

class FileLogWriter implements LogWriter
{

    protected $file;

    protected $delimiter;

    protected $endString;

    public function __construct($file = "/dev/null", $delimiter = ';', $endString = "\n")
    {
        $this->file = $file;
        $this->delimiter = $delimiter;
        $this->endString = $endString;
    }

    public function logWrite($id, $level, $message)
    {
        $string = $id . $this->delimiter . $level . $this->delimiter . $message . $this->endString;
        file_put_contents($this->file, $string, FILE_APPEND);
    }
}
