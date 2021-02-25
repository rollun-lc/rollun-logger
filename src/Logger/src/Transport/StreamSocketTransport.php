<?php

namespace rollun\logger\Transport;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class StreamSocketTransport implements TransportInterface
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var $protocol
     */
    private $protocol;

    /**
     * @var resource|null
     */
    private $socket;

    /**
     * @var string
     */
    private $buffer = '';

    /**
     * Socket writing timeout in seconds (for stream_set_timeout)
     *
     * @var float
     */
    private $timeout = 15;

    /**
     * Timeout for writing function in seconds (0 to disable)
     * Checks that at least some data has been sent for a given period
     *
     * @var float
     */
    private $writingTimeout = 0;

    /**
     * The connection timeout, in seconds.
     *
     * @var float|null
     */
    private $connectionTimeout;

    /**
     * @var int|null
     */
    private $lastSentBytes = null;

    /**
     * Unix timestamp
     *
     * @var int
     */
    private $lastWritingAt;

    /**
     * $error_code from fsockopen
     *
     * @var int|null
     */
    private $errno;

    /**
     * $error_message from fsockopen
     *
     * @var string|null
     */
    private $errstr;

    public function __construct(string $host, int $port, Protocol $protocol, array $options = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->protocol = $protocol;

        if (isset($options['timeout'])) {
            $this->setTimeout($options['timeout']);
        }

        if (isset($options['writingTimeout'])) {
            $this->setWritingTimeout($options['writingTimeout']);
        }

        if (isset($options['connectionTimeout'])) {
            $this->setConnectionTimeout($options['connectionTimeout']);
        } else {
            $defaultSocketTimeout = ini_get('default_socket_timeout');
            $this->setConnectionTimeout($defaultSocketTimeout > 0 ? $defaultSocketTimeout: null);
        }
    }

    public function write(string $message): void
    {
        $this->buffer .= $message;
    }

    public function flush(): void
    {
        if ($this->isBufferEmpty()) {
            return;
        }
        $this->connectIfNotConnected();
        $this->writeToSocket($this->buffer);
        $this->clearBuffer();
    }

    public function close(): void
    {
        $this->closeSocket();
    }

    public function getName(): string
    {
        return $this->getConnectionString();
    }

    /**
     * Set write timeout. Only has effect before we connect.
     *
     * @see http://php.net/manual/en/function.stream-set-timeout.php
     * @param float $seconds
     * @return StreamSocketTransport
     */
    public function setTimeout(float $seconds): self
    {
        $this->validateTimeout($seconds);
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set writing timeout. Only has effect during connection in the writing cycle.
     *
     * @param float $seconds 0 for no timeout
     * @return StreamSocketTransport
     */
    public function setWritingTimeout(float $seconds): self
    {
        $this->validateTimeout($seconds);
        $this->writingTimeout = $seconds;

        return $this;
    }

    /**
     * Set connection timeout.  Only has effect before we connect.
     *
     * @see http://php.net/manual/en/function.fsockopen.php
     * @param float|null $seconds
     * @return StreamSocketTransport
     */
    public function setConnectionTimeout(?float $seconds): self
    {
        if (!is_null($seconds)) {
            $this->validateTimeout($seconds);
        }
        $this->connectionTimeout = $seconds;

        return $this;
    }

    private function validateTimeout($value)
    {
        $ok = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($ok === false || $value < 0) {
            throw new InvalidArgumentException("Timeout must be 0 or a positive float (got $value)");
        }
    }
    private function connectIfNotConnected()
    {
        if ($this->isConnected()) {
            return;
        }
        $this->connect();
    }

    private function connect(): void
    {
        $this->createSocketResource();
        $this->setSocketTimeout();
    }

    private function createSocketResource(): void
    {
        $socket = $this->fsockopen();

        if (!$socket) {
            $connectionString = $this->getConnectionString();
            throw new UnexpectedValueException("Failed connecting to $connectionString ($this->errno: $this->errstr)");
        }
        $this->socket = $socket;
    }

    protected function getConnectionString(): string
    {
        return $this->protocol->getValue() . '://' . $this->host;
    }

    /**
     * Wrapper to allow mocking
     *
     * @return resource|false
     */
    protected function fsockopen()
    {
        return @fsockopen($this->getConnectionString(), $this->port, $this->errno, $this->errstr, $this->connectionTimeout);
    }

    private function writeToSocket(string $data): void
    {
        $length = strlen($data);
        $sent = 0;
        $this->lastSentBytes = $sent;
        while ($this->isConnected() && $sent < $length) {
            if (0 == $sent) {
                $chunk = $this->fwrite($data);
            } else {
                $chunk = $this->fwrite(substr($data, $sent));
            }
            if ($chunk === false) {
                throw new RuntimeException("Could not write to socket");
            }
            $sent += $chunk;
            $socketInfo = $this->streamGetMetadata();
            if ($socketInfo['timed_out']) {
                throw new RuntimeException("Write timed-out");
            }

            if ($this->writingIsTimedOut($sent)) {
                throw new RuntimeException("Write timed-out, no data sent for `{$this->writingTimeout}` seconds, probably we got disconnected (sent $sent of $length)");
            }
        }
        if (!$this->isConnected() && $sent < $length) {
            throw new RuntimeException("End-of-file reached, probably we got disconnected (sent $sent of $length)");
        }
    }

    private function setSocketTimeout(): void
    {
        if (!$this->streamSetTimeout()) {
            throw new UnexpectedValueException("Failed setting timeout with stream_set_timeout()");
        }
    }

    /**
     * @see http://php.net/manual/en/function.stream-set-timeout.php
     */
    protected function streamSetTimeout(): bool
    {
        $seconds = floor($this->timeout);
        $microseconds = round(($this->timeout - $seconds) * 1e6);

        return stream_set_timeout($this->socket, (int) $seconds, (int) $microseconds);
    }

    /**
     * Check to see if the socket is currently available.
     *
     * UDP might appear to be connected but might fail when writing.  See http://php.net/fsockopen for details.
     */
    public function isConnected(): bool
    {
        return is_resource($this->socket)
            && !feof($this->socket);  // on TCP - other party can close connection.
    }

    /**
     * Wrapper to allow mocking
     *
     * @param string $data
     * @return false|int
     */
    protected function fwrite(string $data)
    {
        return @fwrite($this->socket, $data);
    }

    /**
     * Wrapper to allow mocking
     */
    protected function streamGetMetadata(): array
    {
        return stream_get_meta_data($this->socket);
    }

    private function writingIsTimedOut(int $sent): bool
    {
        $writingTimeout = (int) floor($this->writingTimeout);
        if (0 === $writingTimeout) {
            return false;
        }

        if ($sent !== $this->lastSentBytes) {
            $this->lastWritingAt = time();
            $this->lastSentBytes = $sent;

            return false;
        } else {
            usleep(100);
        }

        if ((time() - $this->lastWritingAt) >= $writingTimeout) {
            $this->closeSocket();

            return true;
        }

        return false;
    }

    /**
     * Close socket, if open
     */
    protected function closeSocket(): void
    {
        if (!is_resource($this->socket)) {
            return;
        }

        fclose($this->socket);
        $this->socket = null;
    }

    protected function clearBuffer(): void
    {
        $this->buffer = '';
    }

    protected function isBufferEmpty(): bool
    {
        return $this->buffer === '';
    }
}