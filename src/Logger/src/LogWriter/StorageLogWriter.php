<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 02.06.17
 * Time: 14:28
 */

namespace rollun\logger\LogWriter;

use Zend\Cache\Storage\StorageInterface;

class StorageLogWriter implements LogWriterInterface
{
    const KEY_LOGS = 'logs';
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * StorageLogWriter constructor.
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function logWrite($id, $level, $message)
    {
        $this->storage->setItem($id, "$level|$message");
    }
}
