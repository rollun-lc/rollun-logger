<?php

declare(strict_types=1);

namespace rollun\logger\Prometheus;

use Prometheus\Storage\Adapter;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

/**
 * Class Collector
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class Collector
{
    public const DEFAULT_REDIS_PORT = 6379;

    /**
     * @var CollectorRegistry
     */
    protected $collector;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * Prometheus constructor.
     */
    public function __construct()
    {
        // prepare Redis data
        $redisHost = getenv('PROMETHEUS_REDIS_HOST');
        $redisPort = empty(getenv('PROMETHEUS_REDIS_PORT')) ? self::DEFAULT_REDIS_PORT : getenv('PROMETHEUS_REDIS_PORT');

        if (!empty($redisHost) && !empty($redisPort)) {
            Redis::setDefaultOptions(['host' => (string) $redisHost, 'port' => (int) $redisPort, 'read_timeout' => '10']);
            $this->adapter = new Redis();
        } else {
            $this->adapter = new InMemory();
        }

        $this->collector = new CollectorRegistry($this->getAdapter());
    }

    /**
     * @return CollectorRegistry
     */
    public function getCollectorRegistry(): CollectorRegistry
    {
        return $this->collector;
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }
}
