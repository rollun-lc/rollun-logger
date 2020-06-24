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
    /**
     * @var string
     */
    protected $redisHost;

    /**
     * @var string
     */
    protected $redisPort = '6379';

    /**
     * @var CollectorRegistry
     */
    protected $collector;

    /**
     * Prometheus constructor.
     */
    public function __construct()
    {
        if (!empty(getenv('PROMETHEUS_REDIS_HOST'))) {
            $this->redisHost = getenv('PROMETHEUS_REDIS_HOST');
        }
        if (!empty(getenv('PROMETHEUS_REDIS_PORT'))) {
            $this->redisPort = getenv('PROMETHEUS_REDIS_PORT');
        }

        if (!empty($this->redisHost) && !empty($this->redisPort)) {
            Redis::setDefaultOptions(['host' => $this->redisHost, 'port' => $this->redisPort, 'read_timeout' => '10']);
            $this->collector = new CollectorRegistry(new Redis());
        } else {
            $this->collector = new CollectorRegistry(new InMemory());
        }
    }

    /**
     * @return CollectorRegistry
     */
    public function __invoke(): CollectorRegistry
    {
        return $this->collector;
    }
}
