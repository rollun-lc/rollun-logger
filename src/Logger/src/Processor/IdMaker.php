<?php

namespace rollun\logger\Processor;

use rollun\logger\LifeCycleToken;
use Zend\Log\Processor\ProcessorInterface;

class IdMaker implements ProcessorInterface
{

    /**
     * @param array $event event data
     * @return array event data
     */
    public function process(array $event)
    {
        if (!isset($event['id'])) {
            $event['id'] = $this->makeId();
        }
        return $event;
    }

    public function makeId()
    {
        list($usec, $sec) = explode(" ", microtime());
        $timestamp = (int) ($sec - date('Z')) . '.' . (int) ($usec * 1000 * 1000);
        $idGenerator = LifeCycleToken::IdGenerate(8);
        $id = $timestamp . '_' . $idGenerator; //1512570082.960175_VFSOODML
        return $id;
    }

}
