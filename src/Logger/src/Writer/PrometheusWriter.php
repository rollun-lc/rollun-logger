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
     * @var string
     */
    protected $serviceName;

    /**
     * @inheritDoc
     */
    public function __construct(CollectorRegistry $collector, PushGateway $pushGateway, string $jobName, string $type, array $options = null)
    {
        $this->collector = $collector;
        $this->pushGateway = $pushGateway;
        $this->jobName = $jobName;
        $this->type = $type;
        $this->serviceName = (string)getenv('SERVICE_NAME');

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

        // prepare prometheus data
        $event['prometheusMetricId'] = isset($event['context']['metricId']) ? (string)$event['context']['metricId'] : null;
        $event['prometheusValue'] = isset($event['context']['value']) ? (float)$event['context']['value'] : null;
        $event['prometheusGroups'] = isset($event['context']['groups']) ? (array)$event['context']['groups'] : [];
        $event['prometheusLabels'] = isset($event['context']['labels']) ? (array)$event['context']['labels'] : [];

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
        return !empty(getenv('PROMETHEUS_HOST')) && !empty($this->serviceName) && !empty($event['prometheusMetricId']) && !empty($event['prometheusValue']);
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
        // prepare namespace
        $this->namespace = str_replace('-', '_', trim(strtolower($this->serviceName)));

        $methodName = 'write' . ucfirst($this->type);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($event['prometheusMetricId'], $event['prometheusValue'], $event['prometheusGroups'], $event['prometheusLabels']);
        }
    }

    /**
     * @param string $metricId
     * @param float  $value
     * @param array  $groups
     * @param array  $labels
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    protected function writeGauge(string $metricId, float $value, array $groups, array $labels)
    {
        $gauge = $this->collector->getOrRegisterGauge($this->namespace, $metricId, $this->serviceName, $labels);
        $gauge->set($value, $labels);

        $this->pushGateway->pushAdd($this->collector, $this->jobName, $groups);
    }

    /**
     * @param string $metricId
     * @param float  $value
     * @param array  $groups
     * @param array  $labels
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    protected function writeCounter(string $metricId, float $value, array $groups, array $labels)
    {
        $counter = $this->collector->getOrRegisterCounter($this->namespace, $metricId, $this->serviceName, $labels);
        $counter->incBy($value, $labels);

        $this->pushGateway->pushAdd($this->collector, $this->jobName, $groups);
    }
}
