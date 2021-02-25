<?php


namespace rollun\logger\Writer;


use InvalidArgumentException;
use rollun\logger\Transport\TransportInterface;
use RuntimeException;
use Throwable;
use Traversable;
use Zend\Log\Writer\AbstractWriter;

abstract class TransportAbstractWriter extends AbstractWriter
{
    protected const MAX_ATTEMPTS = 3;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var TransportInterface
     */
    protected $transport;

    public function __construct($transport, array $options = [])
    {
        if ($transport instanceof Traversable) {
            $transport = iterator_to_array($transport);
        }

        if (is_array($transport)) {
            $options = array_merge(
                [
                    'ignore_error' => false,     // Suppress writer exceptions
                    'auto_flash' => true,
                ], $transport['options'] ?? []
            );

            if (!isset($transport['formatter'])) {
                throw new InvalidArgumentException('You must pass formatter');
            }

            $transport['options'] = $options;
            parent::__construct($transport);

            $transport = $transport['client'] ?? null;

            $transportOptions = $transport['options'] ?? [];

            $transport = $this->createTransport($transport['host'], $transport['port'], $transportOptions);
        }

        if (!$transport instanceof TransportInterface) {
            throw new InvalidArgumentException('You must pass a valid rollun\logger\TransportInterface');
        }

        $this->transport = $transport;
        $this->options = $options;
    }

    /**
     * Create concrete transport (tcp, udp, etc) by host and port
     *
     * @param string $host
     * @param int $port
     * @return TransportInterface
     */
    abstract function createTransport(string $host, int $port, $options = []): TransportInterface;

    public function __destruct()
    {
        $this->transport->close();
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
        $message = $this->formatter->format($event);
        $this->transport->write($message);
        $this->flushMessage();
    }

    private function flushMessage()
    {
        $exception = null;
        for ($attempts = 0; $attempts <= self::MAX_ATTEMPTS; $attempts++) {
            try {
                if ($this->options['auto_flash']) {
                    $this->transport->flush();
                    return;
                }
            } catch (Throwable $e) {
                $exception = $e;
            }
        }
        if (!$this->options['ignore_error']) {
            throw new RuntimeException('Error sending messages to ' . $this->transport->getName(), 0, $exception);
        }
    }
}