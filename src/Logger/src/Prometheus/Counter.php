<?php

declare(strict_types=1);

namespace rollun\logger\Prometheus;

use Prometheus\Storage\Adapter;

/**
 * Class Counter
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class Counter extends \Prometheus\Counter
{
    /**
     * @param int   $count
     * @param array $labels
     */
    public function set($count, array $labels = [])
    {
        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storageAdapter->updateCounter(
            [
                'name'        => $this->getName(),
                'help'        => $this->getHelp(),
                'type'        => $this->getType(),
                'labelNames'  => $this->getLabelNames(),
                'labelValues' => $labels,
                'value'       => $count,
                'command'     => Adapter::COMMAND_SET,
            ]
        );
    }
}
