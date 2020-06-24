<?php
declare(strict_types=1);

namespace rollun\logger\Writer;

use Prometheus\CollectorRegistry;
use rollun\logger\Prometheus\PushGateway;
use Zend\Log\Writer\AbstractWriter;

/**
 * Class PrometheusWriter
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class PrometheusWriter extends AbstractWriter
{
    /**
     * @var CollectorRegistry
     */
    protected $collector;

    /**
     * @var PushGateway
     */
    protected $pushGateway;

    /**
     * @var string
     */
    protected $jobName;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @inheritDoc
     */
    public function __construct(CollectorRegistry $collector, PushGateway $pushGateway, string $jobName, string $type, array $options = null)
    {
        $this->collector = $collector;
        $this->pushGateway = $pushGateway;
        $this->jobName = $jobName;
        $this->type = $type;

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

        if ($this->isValid($event)) {
            parent::write($event);
        }
    }

    /**
     * @param array $event
     *
     * @return bool
     */
    protected function isValid(array $event): bool
    {
        return !empty(getenv('PROMETHEUS_HOST')) && !empty(getenv('SERVICE_NAME')) && !empty($event['context']['metricId']) && isset($event['context']['value']);
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
        // prepare namespace
        $this->namespace = str_replace('-', '_', trim(strtolower(getenv('SERVICE_NAME'))));

        $methodName = 'write' . ucfirst($this->type);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event);
        }
    }

    /**
     * @param array $event
     */
    protected function writeGauge(array $event)
    {
        $gauge = $this->collector->getOrRegisterGauge($this->namespace, $event['context']['metricId'], '');
        $gauge->set($event['context']['value']);

        $this->pushGateway->pushAdd($this->collector, $this->jobName, []);
    }

    /**
     * @param array $event
     */
    protected function writeCounter(array $event)
    {
        $counter = $this->collector->getOrRegisterCounter($this->namespace, $event['context']['metricId'], '');
        $counter->incBy($event['context']['value']);

        $this->pushGateway->pushAdd($this->collector, $this->jobName, []);
    }
}
