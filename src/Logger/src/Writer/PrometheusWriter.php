<?php
declare(strict_types=1);

namespace rollun\logger\Writer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Prometheus\Collector as PrometheusCollector;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\Adapter;
use rollun\logger\Prometheus\Collector;
use rollun\logger\Prometheus\PushGateway;

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
    public const METHOD_POST = 'post';
    public const METHOD_PUT = 'put';
    public const METHOD_DELETE = 'delete';
    public const METHODS = [self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE];

    public const METRIC_ID = 'metricId';
    public const VALUE = 'value';
    public const GROUPS = 'groups';
    public const LABELS = 'labels';
    public const METHOD = 'method';
    public const REFRESH = 'refresh';
    public const SERVICE = 'service';
    public const ACTION = 'action';
    public const WITH_SERVICE_NAME = self::SERVICE;

    public const KEYS = [
        self::METRIC_ID,
        self::VALUE,
        self::GROUPS,
        self::LABELS,
        self::METHOD,
        self::REFRESH,
        self::SERVICE,
        self::ACTION,
    ];

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
    public function __construct(
        Collector $collector,
        PushGateway $pushGateway,
        string $jobName,
        string $type,
        array $options = null
    ) {
        $this->collector = $collector;
        $this->pushGateway = $pushGateway;
        $this->jobName = $jobName;
        $this->type = $type;

        parent::__construct($options);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function write(array $event): void
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
     *
     * @return array
     * @throws Exception
     */
    protected function prepareData(array $event): array
    {
        // required data
        $event['prometheusMetricId'] = isset($event['context'][self::METRIC_ID]) ? (string)$event['context'][self::METRIC_ID] : null;
        $event['prometheusValue'] = isset($event['context'][self::VALUE]) ? (float)$event['context'][self::VALUE] : 0;

        // prepare groups
        $event['prometheusGroups'] = isset($event['context'][self::GROUPS]) ? (array)$event['context'][self::GROUPS] : [];
        $serviceName = $event['context'][self::SERVICE] ?? getenv('SERVICE_NAME');
        $withName = $serviceName ? true : false;
        if ($withName && $serviceName) {
            $event['prometheusGroups']['service'] = $serviceName;
        }

        // action
        if (isset($event['context'][self::ACTION])) {
            $event['prometheusGroups']['action'] = $event['context'][self::ACTION];
        }

        // other
        $event['prometheusLabels'] = isset($event['context'][self::LABELS]) ? (array)$event['context'][self::LABELS] : [];
        $event['prometheusMethod'] = isset($event['context'][self::METHOD]) ? (string)$event['context'][self::METHOD] : self::METHOD_POST;
        $event['prometheusRefresh'] = !empty($event['context'][self::REFRESH]);

        return $event;
    }

    /**
     * @param array $event
     *
     * @return bool
     */
    protected function isValid(array $event): bool
    {
        if (empty(getenv('PROMETHEUS_HOST')) || empty($event['prometheusMetricId'])) {
            return false;
        }

        //validate context keys
        foreach ($event['context'] as $key => $value) {
            if (!in_array($key, self::KEYS)) {
                return false;
            }
        }

        //required context data
        if (!in_array($event['prometheusMethod'], self::METHODS)) {
            return false;
        }

        return true;
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
     * @throws GuzzleException
     * @throws MetricsRegistrationException
     */
    protected function writeGauge(array $event)
    {
        $gauge = $this->getCollectorRegistry()->getOrRegisterGauge(
            '',
            $event['prometheusMetricId'],
            '',
            $event['prometheusLabels']
        );
        $gauge->set($event['prometheusValue'], $event['prometheusLabels']);

        $this->send($gauge, $event['prometheusMethod'], $event['prometheusGroups']);
    }

    /**
     * @param array $event
     *
     * @throws GuzzleException
     * @throws MetricsRegistrationException
     */
    protected function writeCounter(array $event)
    {
        $counter = $this->getCollectorRegistry()->getOrRegisterCounter(
            '',
            $event['prometheusMetricId'],
            '',
            $event['prometheusLabels']
        );

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
     * @throws GuzzleException
     */
    protected function send(PrometheusCollector $collector, string $method, array $groups)
    {
        $this->pushGateway->doRequest($this->getCollectorRegistry(), $collector, $this->jobName, $groups, $method);
    }
}
