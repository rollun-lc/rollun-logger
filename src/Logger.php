<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 09.01.17
 * Time: 18:32
 */

namespace rollun\logger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use rollun\dic\InsideConstruct;
use rollun\logger\LogWriter\FileLogWriter;
use rollun\logger\LogWriter\LogWriter;

class Logger extends AbstractLogger
{
    protected $logWriter;

    protected $levelEnum = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug'
    ];

    public function __construct(LogWriter $logWriter = null)
    {
        InsideConstruct::setConstructParams();
        if (!isset($this->logWriter)) {
            $this->logWriter = new FileLogWriter();
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return string
     */
    public function log($level, $message, array $context = array())
    {
        $replace = [];
        if (!in_array($level, $this->levelEnum)) {
            throw new InvalidArgumentException("Invalid Level");
        }
        foreach ($context as $key => $value) {
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        $split = preg_split('/\|/', strtr($message, $replace), 2, PREG_SPLIT_NO_EMPTY);
        if (count($split) == 2) {
            $split[0] = trim($split[0]);
            $id = is_numeric($split[0]) ? $split[0] : (new \DateTime($split[0]))->getTimestamp();
            $message = $split[1];
        } else {
            $id = microtime(true) - date('Z');
            $message = $split[0];
        }
        $id = base64_encode(uniqid("", true) . '_' . $id);
        $this->logWriter->logWrite($id, $level, $message);
        return $id;
    }
}
