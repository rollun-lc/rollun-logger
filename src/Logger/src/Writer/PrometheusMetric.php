<?php
declare(strict_types=1);

namespace rollun\logger\Writer;

use Prometheus\CollectorRegistry;
use Prometheus\PushGateway;
use Prometheus\Storage\InMemory;
use Zend\Log\Writer\AbstractWriter;

/**
 * Class Prometheus
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class PrometheusMetric extends AbstractWriter
{
    /**
     * Prometheus job name
     */
    const JOB_NAME = 'logger_job';

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port = '9091';

    /**
     * @inheritDoc
     */
    public function __construct($options = null)
    {
        if (!empty($options['host'])) {
            $this->host = $options['host'];
        }

        if (!empty($options['port'])) {
            $this->port = $options['port'];
        }

        parent::__construct($options);
    }

    /**
     * @inheritDoc
     */
    public function write(array $event)
    {
        // call formatter
        if ($this->hasFormatter()) {
            $event = $this->getFormatter()->format($event);
        }

        if (!$this->isValid($event)) {
            return '';
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
        return !empty($this->host) && !empty(getenv('SERVICE_NAME')) && !empty($event['context']['metricId']) && isset($event['context']['value']);
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
        // prepare namespace
        $namespace = str_replace('-', '_', trim(strtolower(getenv('SERVICE_NAME'))));

        $registry = new CollectorRegistry(new InMemory());

        $counter = $registry->getOrRegisterCounter($namespace, $event['context']['metricId'], '');
        $counter->incBy($event['context']['value']);

        $pushGateway = new PushGateway("{$this->host}:{$this->port}");

        try {
            $pushGateway->push($registry, self::JOB_NAME, []);
        } catch (\RuntimeException $e) {
            // skip unexpected status code exception
        }
    }
}
