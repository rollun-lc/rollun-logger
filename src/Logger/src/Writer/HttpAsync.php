<?php
declare(strict_types=1);

namespace rollun\logger\Writer;

use InvalidArgumentException;
use rollun\logger\LifeCycleToken;
use RuntimeException;

/**
 * Class HttpAsync
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class HttpAsync extends AbstractWriter
{
    /**
     * Request method
     */
    const METHOD = 'POST';

    /**
     * @var string
     */
    protected $url = '';

    /**
     * @var bool
     */
    protected $isServerAvailable = true;

    /**
     * @inheritDoc
     */
    public function __construct($options = null)
    {
        if (!empty($options['url'])) {
            if (!filter_var($options['url'], FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('URL is invalid');
            }

            $this->url = $options['url'];
        }

        parent::__construct($options);
    }

    /**
     * @inheritDoc
     */
    public function write(array $event): void
    {
        if (empty($this->url) || !$this->isServerAvailable || !$this->isValid($event)) {
            return;
        }

        parent::write($event);
    }

    /**
     * @param array $event
     *
     * @return bool
     */
    protected function isValid(array $event): bool
    {
        return true;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    protected function parseUrl(array $event): array
    {
        return parse_url($this->url);
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
        // parse host, path, port from url
        $parts = $this->parseUrl($event);

        $lifeCycleToken = $event[LifeCycleToken::KEY_LIFECYCLE_TOKEN] ?? null;

        // call formatter
        if ($this->hasFormatter()) {
            $event = $this->getFormatter()->format($event);
        }

        // array to json
        $data = json_encode($event);

        // prepare port
        $port = isset($parts['port']) ? (int)$parts['port'] : 80;

        // prepare path
        $path = isset($parts['path']) ? $parts['path'] : '/';

        // prepare out
        $out = '';
        $out .= self::METHOD . " $path HTTP/1.1\r\n";
        $out .= "Host: {$parts['host']}\r\n";
        $out .= "Accept: application/json\r\n";
        $out .= "Content-Type: application/json\r\n";
        if ($lifeCycleToken !== null) {
            $out .= "LifeCycleToken: $lifeCycleToken\r\n";
        }
        $out .= "Content-Length: " . strlen($data) . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        $out .= $data;

        // send
        $this->send($parts['host'], $port, $out);
    }

    /**
     * @param string $host
     * @param int    $port
     * @param string $out
     */
    protected function send(string $host, int $port, string $out)
    {
        $fp = fsockopen($host, $port, $errno, $errstr, 0.5);

        if ($fp === false) {
            $this->isServerAvailable = false;
            throw new RuntimeException("Cannot open socket connection to {$host}:{$port}. Error: {$errstr} (Code: {$errno})");
        }

        $result = fwrite($fp, $out);

        if ($result === false) {
            fclose($fp);
            $this->isServerAvailable = false;
            throw new RuntimeException("Failed to write data to {$host}:{$port}");
        }

        // Read response to check for errors
        stream_set_timeout($fp, 1); // 1s read timeout
        $statusLine = fgets($fp, 2048) ?: '';
        fclose($fp);

        // Check for HTTP error status
        if (preg_match('/HTTP\/1\.1 (\d+)/', $statusLine, $matches)) {
            $statusCode = (int) $matches[1];
            if ($statusCode >= 400) {
                throw new RuntimeException("Server returned error {$statusCode} for {$this->url}");
            }
        }
    }
}
