<?php
declare(strict_types=1);

namespace rollun\logger\Writer;

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

    const PROMETHEUS_METRIC_ID = 'prometheusMetricId';
    const PROMETHEUS_VALUE = 'prometheusValue';
    const PROMETHEUS_GROUPS = 'prometheusGroups';
    const PROMETHEUS_LABELS = 'prometheusLabels';
    const PROMETHEUS_METHOD = 'prometheusMethod';
    const PROMETHEUS_REFRESH = 'prometheusRefresh';
    const PROMETHEUS_KEYS = [
        self::PROMETHEUS_METRIC_ID,
        self::PROMETHEUS_VALUE,
        self::PROMETHEUS_GROUPS,
        self::PROMETHEUS_LABELS,
        self::PROMETHEUS_METHOD,
        self::PROMETHEUS_REFRESH
    ];

    const SERVICE_NAME_KEY = 'service';

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
     * @throws \Exception
     */
    public function write(array $event)
    {
        // call formatter
        if ($this->hasFormatter()) {
            $event = $this->getFormatter()->format($event);
        }

        // prepare prometheus data
        $event[self::PROMETHEUS_METRIC_ID] = isset($event['context']['metricId'])
            ? (string)$event['context']['metricId']
            : null;
        $event[self::PROMETHEUS_VALUE] = isset($event['context']['value']) ? (float)$event['context']['value'] : 0;
        $event[self::PROMETHEUS_GROUPS] = isset($event['context']['groups']) ? (array)$event['context']['groups'] : [];
        $event = $this->addServiceNameToGroup($event);
        $event[self::PROMETHEUS_LABELS] = isset($event['context']['labels']) ? (array)$event['context']['labels'] : [];
        $event[self::PROMETHEUS_METHOD] = isset($event['context']['method'])
            ? (string)$event['context']['method']
            : self::METHOD_POST;
        $event[self::PROMETHEUS_REFRESH] = !empty($event['context']['refresh']);

        if ($this->isValid($event)) {
            parent::write($event);
        }
    }

    /**
     * @param array $event
     * @return array
     */
    private function addServiceNameToGroup(array $event): array
    {
        $serviceName = getenv('SERVICE_NAME');
        $withName = getenv('WITH_SERVICE_NAME') === 'true';
        if ($withName && $serviceName) {
            $event[self::PROMETHEUS_GROUPS][self::SERVICE_NAME_KEY] = $serviceName;
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return bool
     * @throws \Exception
     */
    protected function isValid(array $event): bool
    {
        // check event array has prometheus keys
        foreach (self::PROMETHEUS_KEYS as $key) {
            if (!in_array($key, $event)) {
                throw new \Exception(sprintf('Not provided Prometheus key: %s', $key));
            }
        }

        // check prometheus method
        if (!in_array($event[self::PROMETHEUS_METHOD], self::METHODS)) {
            throw new \Exception(sprintf('PROMETHEUS_METHOD is not supported: %s', $event[self::PROMETHEUS_METHOD]));
        }

        // check required params
        if (empty(getenv('PROMETHEUS_HOST')) || empty($event[self::PROMETHEUS_METRIC_ID])) {
            throw new \Exception('Prometheus host or metric_id is not provided');
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    protected function writeGauge(array $event)
    {
        $gauge = $this->getCollectorRegistry()
            ->getOrRegisterGauge(
                '',
                $event[self::PROMETHEUS_METRIC_ID],
                '',
                $event[self::PROMETHEUS_LABELS]
            );
        $gauge->set($event[self::PROMETHEUS_VALUE], $event[self::PROMETHEUS_LABELS]);

        $this->send($event[self::PROMETHEUS_METHOD], $event[self::PROMETHEUS_GROUPS]);
    }

    /**
     * @param array $event
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    protected function writeCounter(array $event)
    {
        $counter = $this->getCollectorRegistry()
            ->getOrRegisterCounter('', $event[self::PROMETHEUS_METRIC_ID], '', $event[self::PROMETHEUS_LABELS]);

        if ($event[self::PROMETHEUS_REFRESH]) {
            $counter->set($event[self::PROMETHEUS_VALUE], $event[self::PROMETHEUS_LABELS]);
        } else {
            $counter->incBy($event[self::PROMETHEUS_VALUE], $event[self::PROMETHEUS_LABELS]);
        }

        $this->send($event[self::PROMETHEUS_METHOD], $event[self::PROMETHEUS_GROUPS]);
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
