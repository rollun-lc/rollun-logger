<?php
declare(strict_types=1);

namespace rollun\logger\Prometheus;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

/**
 * Class Collector
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class Collector
{
    const DEFAULT_REDIS_PORT = 6379;

    /**
     * @var CollectorRegistry
     */
    protected $collector;

    /**
     * Prometheus constructor.
     */
    public function __construct()
    {
        // prepare Redis data
        $redisHost = getenv('PROMETHEUS_REDIS_HOST');
        $redisPort = empty(getenv('PROMETHEUS_REDIS_PORT')) ? self::DEFAULT_REDIS_PORT : getenv('PROMETHEUS_REDIS_PORT');

        if (!empty($redisHost) && !empty($redisPort)) {
            Redis::setDefaultOptions(['host' => (string)$redisHost, 'port' => (int)$redisPort, 'read_timeout' => '10']);
            $adapter = new Redis();
        } else {
            $adapter = new InMemory();
        }

        $this->collector = new CollectorRegistry($adapter);
    }

    /**
     * @return CollectorRegistry
     */
    public function __invoke(): CollectorRegistry
    {
        return $this->collector;
    }
}
