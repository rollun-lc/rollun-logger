<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Writer;

use ErrorException;
use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Exception\RuntimeException;
use Traversable;
use rollun\logger\Formatter\Simple as SimpleFormatter;
use Laminas\Stdlib\ErrorHandler;

class Stream extends AbstractWriter
{
    /**
     * Separator between log entries
     *
     * @var string
     */
    protected $logSeparator = PHP_EOL;

    /**
     * URLs whose stream is a dup() of an inherited descriptor (php://stdout, php://stderr, php://fd/N).
     * Such a handle cannot be opened with O_CLOEXEC, so it is not kept between writes.
     */
    protected const REOPEN_URL_PATTERN = '~^php://(stdout|stderr|fd/\d+)$~i';

    /**
     * Holds the PHP stream to log to.
     *
     * Null when the writer reopens the URL on every write (see $reopenUrl).
     *
     * @var null|resource
     */
    protected $stream = null;

    /**
     * URL that is opened, written and closed on every write instead of being held open.
     *
     * A held php://stdout handle is a dup() of the worker's stdout without close-on-exec, so every
     * process spawned by the worker inherits it for its whole life. In php-fpm that descriptor is the
     * master's catch pipe: an orphaned job keeps it open after the worker exits, the master never sees
     * EOF and its event queue is poisoned (see rollun-callback py2Gb7O7). Opening the URL only for the
     * duration of one write makes the dup unobservable to spawned processes.
     *
     * @var null|string
     */
    protected $reopenUrl = null;

    /**
     * Mode used when reopening $reopenUrl.
     *
     * @var string
     */
    protected $reopenMode = 'a';

    /**
     * Constructor
     *
     * @param  string|resource|array|Traversable $streamOrUrl Stream or URL to open as a stream.
     *     As an array of options, 'persistent' => true keeps php://stdout|stderr|fd/N open between
     *     writes as older versions did (plain files are always held open, but with close-on-exec).
     *     Note that a reopened php://stdout follows whatever descriptor 1 is at write time: a script
     *     that closes STDOUT and opens another file will find its log lines in that file.
     * @param  string|null $mode Mode, only applicable if a URL is given
     * @param  null|string $logSeparator Log separator string
     * @param  null|int $filePermissions Permissions value, only applicable if a filename is given;
     *     when $streamOrUrl is an array of options, use the 'chmod' key to specify this.
     * @throws InvalidArgumentException
     * @throws RuntimeException|ErrorException
     */
    public function __construct($streamOrUrl, $mode = null, $logSeparator = null, $filePermissions = null)
    {
        if ($streamOrUrl instanceof Traversable) {
            $streamOrUrl = iterator_to_array($streamOrUrl);
        }

        $persistent = false;
        if (is_array($streamOrUrl)) {
            parent::__construct($streamOrUrl);
            $mode            = $streamOrUrl['mode'] ?? null;
            $logSeparator    = $streamOrUrl['log_separator'] ?? null;
            $filePermissions = $streamOrUrl['chmod'] ?? $filePermissions;
            $persistent      = (bool) ($streamOrUrl['persistent'] ?? false);
            $streamOrUrl     = $streamOrUrl['stream'] ?? null;
        }

        // Setting the default mode
        if (null === $mode) {
            $mode = 'a';
        }

        if (is_resource($streamOrUrl)) {
            if ('stream' != get_resource_type($streamOrUrl)) {
                throw new InvalidArgumentException(sprintf(
                    'Resource is not a stream; received "%s',
                    get_resource_type($streamOrUrl)
                ));
            }

            if ('a' != $mode) {
                throw new InvalidArgumentException(sprintf(
                    'Mode must be "a" on existing streams; received "%s"',
                    $mode
                ));
            }

            $this->stream = $streamOrUrl;
        } else {
            $streamOrUrl = (string) $streamOrUrl;
            if (self::isPlainFilePath($streamOrUrl) && !str_contains($mode, 'e')) {
                // 'e' = O_CLOEXEC: spawned processes must not inherit the log file descriptor.
                // Only meaningful as a trailing flag; ignored by PHP where O_CLOEXEC is undefined.
                $mode .= 'e';
            }

            ErrorHandler::start();
            if (isset($filePermissions) && ! file_exists($streamOrUrl) && is_writable(dirname($streamOrUrl))) {
                touch($streamOrUrl);
                chmod($streamOrUrl, $filePermissions);
            }
            $stream = fopen($streamOrUrl, $mode, false);
            $error = ErrorHandler::stop();
            if (! $stream) {
                throw new RuntimeException(sprintf(
                    '"%s" cannot be opened with mode "%s"',
                    $streamOrUrl,
                    $mode
                ), 0, $error);
            }

            if (! $persistent && self::isReopenedPerWrite($streamOrUrl)) {
                // Validated that the URL opens; from now on it is opened only for the duration of a write.
                fclose($stream);
                $this->reopenUrl  = $streamOrUrl;
                $this->reopenMode = $mode;
            } else {
                $this->stream = $stream;
            }
        }

        if (null !== $logSeparator) {
            $this->setLogSeparator($logSeparator);
        }

        if ($this->formatter === null) {
            $this->formatter = new SimpleFormatter();
        }
    }

    /**
     * Write a message to the log.
     *
     * @param array $event event data
     * @return void
     * @throws RuntimeException
     */
    protected function doWrite(array $event)
    {
        // Format before opening: no foreign code may run while the transient descriptor exists.
        $line = $this->formatter->format($event) . $this->logSeparator;

        if (null === $this->reopenUrl) {
            fwrite($this->stream, $line);
            return;
        }

        // Own handler level (they nest) so the fopen() warning ends up as the exception's previous
        // instead of being discarded by AbstractWriter::write() on its way out.
        ErrorHandler::start();
        $stream = fopen($this->reopenUrl, $this->reopenMode, false);
        $error = ErrorHandler::stop();
        if (! $stream) {
            // A RuntimeException is caught by AbstractWriter::write(); a TypeError from fwrite(false)
            // would bypass it and leave the ErrorHandler started for the rest of the request.
            throw new RuntimeException(sprintf(
                '"%s" cannot be opened with mode "%s"',
                $this->reopenUrl,
                $this->reopenMode
            ), 0, $error);
        }
        try {
            fwrite($stream, $line);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Whether the URL must be opened per write rather than held between writes.
     *
     * php://stdout, php://stderr and php://fd/N dup() an inherited descriptor and ignore the 'e' mode
     * flag, so the only way to keep the dup away from spawned processes is not to hold it.
     *
     * Not applied in the CLI SAPI when the STDOUT constant is missing (script fed via stdin): there the
     * first php://stdout handle is the real descriptor 1 itself, and closing it would close the
     * process's stdout.
     */
    protected static function isReopenedPerWrite(string $url): bool
    {
        if (! preg_match(self::REOPEN_URL_PATTERN, $url)) {
            return false;
        }

        return PHP_SAPI !== 'cli' || defined('STDOUT');
    }

    /**
     * Whether the URL is served by the plain file wrapper (no scheme, or file://), the only wrapper
     * that honours the 'e' (O_CLOEXEC) mode flag.
     */
    protected static function isPlainFilePath(string $url): bool
    {
        if (! preg_match('~^([a-z][a-z0-9+.\-]*)://~i', $url, $matches)) {
            return true;
        }

        return 0 === strcasecmp($matches[1], 'file');
    }

    /**
     * Set log separator string
     *
     * @param string $logSeparator
     * @return Stream
     */
    public function setLogSeparator(string $logSeparator)
    {
        $this->logSeparator = (string) $logSeparator;
        return $this;
    }

    /**
     * Get log separator string
     *
     * @return string
     */
    public function getLogSeparator()
    {
        return $this->logSeparator;
    }

    /**
     * Close the stream resource.
     *
     * @return void
     */
    public function shutdown()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
