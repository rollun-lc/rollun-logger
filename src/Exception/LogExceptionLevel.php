<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20.01.17
 * Time: 15:50
 */

namespace rollun\logger\Exception;

use Psr\Log\LogLevel;

class LogExceptionLevel
{
    const EMERGENCY = 7;
    const ALERT = 6;
    const CRITICAL = 5;
    const ERROR = 4;
    const WARNING = 3;
    const NOTICE = 2;
    const INFO = 1;
    const DEBUG = 0;

    const LOG_LEVEL = [
        0 => LogLevel::DEBUG,
        1 => LogLevel::INFO,
        2 => LogLevel::NOTICE,
        3 => LogLevel::WARNING,
        4 => LogLevel::ERROR,
        5 => LogLevel::CRITICAL,
        6 => LogLevel::ALERT,
        7 => LogLevel::EMERGENCY,
    ];

    public static function getLoggerLevelByCode($code)
    {
        if (array_key_exists($code, self::LOG_LEVEL)) {
            $logLevels = self::LOG_LEVEL;
            return $logLevels[$code];
        }
        return '';
    }
}
