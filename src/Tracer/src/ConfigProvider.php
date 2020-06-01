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
                'host'        => getenv('TRACER_HOST'),
                'port'        => getenv('TRACER_PORT'),
                'serviceName' => getenv('SERVICE_NAME'),
                'debugEnable' => !empty(getenv('APP_DEBUG')) && getenv('APP_DEBUG') !== 'false',
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
