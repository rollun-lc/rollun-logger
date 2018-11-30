<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Zend\Log\Processor\PsrPlaceholder;

/**
 * Last hope, if every else not work.
 * Write log in sent path.
 * Class SimpleLogger
 * @package rollun\logger
 */
final class SimpleLogger implements LoggerInterface
{
    use LoggerTrait;

    const DEFAULT_LOGS_PATH = "logs.log";

    /**
     * @var string
     */
    private $receiverPath;

    /**
     * @var PsrPlaceholder
     */
    private $psrPlaceholder;

    /**
     * SimpleLogger constructor.
     */
    public function __construct()
    {
        $receiverPath = getenv("LOGS_RECEIVER");
        if (!$receiverPath || !is_string($receiverPath) || !file_exists($receiverPath) || !is_file($receiverPath)) {
            $receiverPath = "data" . DIRECTORY_SEPARATOR . static::DEFAULT_LOGS_PATH;
            //silent exception if can't create or write to file, and set receiver path to stdout.
            if (@file_put_contents($receiverPath, "", FILE_APPEND) === false) {
                $receiverPath = "php://stdout";
            }
        }
        $this->receiverPath = $receiverPath;
        $this->psrPlaceholder = new PsrPlaceholder();
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $message = $this->psrPlaceholder->process([
            "message" => $message,
            "context" => $context,
        ])["message"];
        file_put_contents($this->receiverPath, $message, FILE_APPEND);
    }
}
