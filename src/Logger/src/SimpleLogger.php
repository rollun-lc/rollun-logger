<?php


namespace rollun\logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use rollun\installer\Command;
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

    const DEFAULT_LOGS_PATH = "simple_logs/logs.dat";

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
        if (!$receiverPath || !is_string($receiverPath) || !file_exists($receiverPath)) {
            $receiverPath = Command::getDataDir() . static::DEFAULT_LOGS_PATH;
            file_put_contents($receiverPath, "", FILE_APPEND);
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