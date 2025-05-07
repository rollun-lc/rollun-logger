<?php

declare(strict_types=1);

namespace rollun\logger\Writer\Factory;

use Psr\Container\ContainerInterface;
use rollun\logger\Prometheus\Collector;
use rollun\logger\Prometheus\PushGateway;
use rollun\logger\Writer\PrometheusWriter;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class PrometheusFactory
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class PrometheusFactory implements FactoryInterface
{
    public const COLLECTOR = 'collector';
    public const JOB_NAME = 'jobName';
    public const DEFAULT_JOB_NAME = 'logger_job';
    public const TYPE = 'type';
    public const TYPE_GAUGE = 'gauge';
    public const TYPE_COUNTER = 'counter';

    /**
     * @var array
     */
    protected $types = [self::TYPE_GAUGE, self::TYPE_COUNTER];

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = (array) $options;

        $collectorClass = empty($config[static::COLLECTOR]) ? Collector::class : $config[static::COLLECTOR];
        if (!is_a($collectorClass, Collector::class, true)) {
            throw new \InvalidArgumentException('Collector class should be instance of ' . Collector::class);
        }

        $jobName = empty($config[static::JOB_NAME]) ? self::DEFAULT_JOB_NAME : $config[static::JOB_NAME];

        if (empty($config[static::TYPE])) {
            throw new \InvalidArgumentException('Prometheus type is required');
        }

        if (!in_array($config[static::TYPE], $this->types)) {
            throw new \InvalidArgumentException('Unknown prometheus type');
        }

        return new PrometheusWriter($container->get($collectorClass), $container->get(PushGateway::class), $jobName, $config[static::TYPE], $config);
    }
}
