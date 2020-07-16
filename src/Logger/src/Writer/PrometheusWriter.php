<?php
declare(strict_types=1);

namespace rollun\logger\Writer;

use Prometheus\Collector as PrometheusCollector;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
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

    const METRIC_ID = 'metricId';
    const VALUE = 'value';
    const GROUPS = 'groups';
    const LABELS = 'labels';
    const METHOD = 'method';
    const REFRESH = 'refresh';
    const WITH_SERVICE_NAME = 'service';

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
     * @inheritDoc
     */
    public function __construct(Collector $collector, PushGateway $pushGateway, string $jobName, string $type, array $options = null)
    {
        $this->collector = $collector;
        $this->pushGateway = $pushGateway;
        $this->jobName = $jobName;
        $this->type = $type;

        parent::__construct($options);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function write(array $event)
    {
        // call formatter
        if ($this->hasFormatter()) {
            $event = $this->getFormatter()->format($event);
        }

        // prepare prometheus data
        $event = $this->prepareData($event);

        if ($this->isValid($event)) {
            parent::write($event);
        }
    }

    /**
     * @param array $event
     * @return array
     * @throws \Exception
     */
    protected function prepareData(array $event): array
    {
        $event['prometheusMetricId'] = isset($event['context'][self::METRIC_ID]) ? (string)$event['context'][self::METRIC_ID] : null;
        $event['prometheusValue'] = isset($event['context'][self::VALUE]) ? (float)$event['context'][self::VALUE] : 0;
        $event['prometheusGroups'] = isset($event['context'][self::GROUPS]) ? (array)$event['context'][self::GROUPS] : [];
        $event = $this->addServiceNameToGroup($event);
        $event['prometheusLabels'] = isset($event['context'][self::LABELS]) ? (array)$event['context'][self::LABELS] : [];
        $event['prometheusMethod'] = isset($event['context']['method']) ? (string)$event['context']['method'] : self::METHOD_POST;
        $event['prometheusRefresh'] = !empty($event['context'][self::REFRESH]);
        return $event;
    }

    /**
     * @param array $event
     * @return bool
     * @throws \Exception
     */
    protected function isValid(array $event): bool
    {
        if (empty(getenv('PROMETHEUS_HOST'))) {
            throw new \Exception('Prometheus host is not provided');
        }
        //required context data
        if (empty($event['prometheusMetricId'])) {
            throw new \Exception('MetricId is not provided');
        }
        if (!in_array($event['prometheusMethod'], self::METHODS)) {
            throw new \Exception(sprintf('PROMETHEUS_METHOD is not supported: %s', $event['prometheusMethod']));
        }
        return true;
    }

    /**
     * @param array $event
     * @return array
     */
    protected function addServiceNameToGroup(array $event): array
    {
        $serviceName = getenv('SERVICE_NAME');
        $withName = $event['context'][self::WITH_SERVICE_NAME] === true;
        if ($withName && $serviceName) {
            $event['prometheusGroups'][self::WITH_SERVICE_NAME] = $serviceName;
        }

        return $event;
    }

    /**
     * @inheritDoc
     */
    protected function doWrite(array $event)
    {
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
        $gauge = $this->getCollectorRegistry()->getOrRegisterGauge('', $event['prometheusMetricId'], '', $event['prometheusLabels']);
        $gauge->set($event['prometheusValue'], $event['prometheusLabels']);

        $this->send($gauge, $event['prometheusMethod'], $event['prometheusGroups']);
    }

    /**
     * @param array $event
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    protected function writeCounter(array $event)
    {
        $counter = $this->getCollectorRegistry()->getOrRegisterCounter('', $event['prometheusMetricId'], '', $event['prometheusLabels']);

        if ($event['prometheusRefresh']) {
            $counter->set($event['prometheusValue'], $event['prometheusLabels']);
        } else {
            $counter->incBy($event['prometheusValue'], $event['prometheusLabels']);
        }

        $this->send($counter, $event['prometheusMethod'], $event['prometheusGroups']);
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
     * @param PrometheusCollector $collector
     * @param string              $method
     * @param array               $groups
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function send(PrometheusCollector $collector, string $method, array $groups)
    {
        $this->pushGateway->doRequest($this->getCollectorRegistry(), $collector, $this->jobName, $groups, $method);
    }
}
