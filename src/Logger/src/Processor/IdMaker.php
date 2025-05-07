<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\logger\Processor;

use rollun\logger\LifeCycleToken;

class IdMaker implements ProcessorInterface
{
    /**
     * @param array $event
     * @return array
     * @throws \Exception
     */
    public function process(array $event): array
    {
        if (!isset($event['id'])) {
            $event['id'] = $this->makeId();
        }

        return $event;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function makeId()
    {
        [$usec, $sec] = explode(" ", microtime());
        $timestamp = (int)($sec - date('Z')) . '.' . (int)($usec * 1000 * 1000);
        $idGenerator = LifeCycleToken::IdGenerate(8);
        $id = $timestamp . '_' . $idGenerator;

        return $id;
    }
}
