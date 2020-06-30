<?php
declare(strict_types=1);

namespace rollun\logger\Writer;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\InMemory;
use rollun\logger\Prometheus\Collector;
use rollun\logger\Prometheus\PushGateway;
use Zend\Log\Writer\AbstractWriter;

/**
 * Class PrometheusWriter
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class PrometheusWriter extends AbstractWriter
{
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';
    const METHODS = [self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE];

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
    public function __construct(Collector $collector, PushGateway $pushGateway, string $jobName, string $type, array $options = null)
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
        $event['prometheusValue'] = isset($event['context']['value']) ? (float)$event['context']['value'] : 0;
        $event['prometheusGroups'] = isset($event['context']['groups']) ? (array)$event['context']['groups'] : [];
        $event['prometheusLabels'] = isset($event['context']['labels']) ? (array)$event['context']['labels'] : [];
        $event['prometheusMethod'] = isset($event['context']['method']) ? (string)$event['context']['method'] : self::METHOD_POST;
        $event['prometheusRefresh'] = !empty($event['context']['refresh']);

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
        return !empty(getenv('PROMETHEUS_HOST')) && !empty($this->serviceName) && !empty($event['prometheusMetricId']) && in_array($event['prometheusMethod'], self::METHODS);
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
            $this->{$methodName}($event);
        }
    }

    /**
     * @param array $event
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    protected function writeGauge(array $event)
    {
        $gauge = $this->getCollectorRegistry()->getOrRegisterGauge($this->namespace, $event['prometheusMetricId'], $this->serviceName, $event['prometheusLabels']);
        $gauge->set($event['prometheusValue'], $event['prometheusLabels']);

        $this->send($event['prometheusMethod'], $event['prometheusGroups']);
    }

    /**
     * @param array $event
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    protected function writeCounter(array $event)
    {
        if (!$this->getAdapter() instanceof InMemory) {
            $counter = $this->getCollectorRegistry()->getOrRegisterCounter($this->namespace, $event['prometheusMetricId'], $this->serviceName, $event['prometheusLabels']);

            if ($event['prometheusRefresh']) {
                $counter->set($event['prometheusValue'], $event['prometheusLabels']);
            } else {
                $counter->incBy($event['prometheusValue'], $event['prometheusLabels']);
            }

            $this->send($event['prometheusMethod'], $event['prometheusLabels']);
        }
    }

    /**
     * @return CollectorRegistry
     */
    protected function getCollectorRegistry(): CollectorRegistry
    {
        return $this->collector->getCollectorRegistry();
    }

    /**
     * @return Adapter
     */
    protected function getAdapter(): Adapter
    {
        return $this->collector->getAdapter();
    }

    /**
     * @param string $method
     * @param array  $groups
     */
    protected function send(string $method, array $groups)
    {
        $this->{$method}($groups);
    }

    /**
     * @param array $groups
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function post(array $groups)
    {
        $this->pushGateway->pushAdd($this->getCollectorRegistry(), $this->jobName, $groups);
    }

    /**
     * @param array $groups
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function put(array $groups)
    {
        $this->pushGateway->push($this->getCollectorRegistry(), $this->jobName, $groups);
    }

    /**
     * @param array $groups
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function delete(array $groups)
    {
        $this->pushGateway->delete($this->getCollectorRegistry(), $this->jobName, $groups);
    }
}
