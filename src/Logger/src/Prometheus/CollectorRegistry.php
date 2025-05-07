<?php

declare(strict_types=1);

namespace rollun\logger\Prometheus;

use Prometheus\Counter as BaseCounter;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\Adapter;

/**
 * Class CollectorRegistry
 *
 * @author    r.ratsun <r.ratsun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class CollectorRegistry extends \Prometheus\CollectorRegistry
{
    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var Counter[]
     */
    protected $counters = [];

    /**
     * @inheritDoc
     */
    public function __construct(Adapter $adapter)
    {
        parent::__construct($adapter);

        $this->adapter = $adapter;
    }

    /**
     * @inheritDoc
     */
    public function registerCounter($namespace, $name, $help, $labels = []): BaseCounter
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->counters[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->counters[$metricIdentifier] = new Counter($this->adapter, $namespace, $name, $help, $labels);

        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @inheritDoc
     */
    public function getCounter($namespace, $name): BaseCounter
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->counters[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }

        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return string
     */
    protected static function metricIdentifier($namespace, $name): string
    {
        return $namespace . ":" . $name;
    }
}
