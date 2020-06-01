<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\tracer;

use Jaeger\Tracer\Tracer;

class ConfigProvider
{
    /**
     * Return default logger config
     */
    public function __invoke()
    {
        return [
            Tracer::class  => [
                'serviceName' => getenv('SERVICE_NAME'),
                'host'        => getenv('TRACER_HOST'),
                'port'        => getenv('TRACER_PORT'),
                'debugEnable' => getenv('TRACER_DEBUG_ENABLE') === 'false' ? false : true,
            ],
            'dependencies' => $this->getDependencies(),
        ];
    }


    /**
     * Return dependencies config
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'abstract_factories' => [
            ],
            'factories'          => [
                Tracer::class => TracerFactory::class,
            ],
            'aliases'            => [],
        ];
    }
}
