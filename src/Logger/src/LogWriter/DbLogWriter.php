<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.01.17
 * Time: 10:26
 */

namespace rollun\logger\LogWriter;

use Zend\Db\TableGateway\TableGateway;

class DbLogWriter implements LogWriterInterface
{

    const KEY_ID = 'id';
    const KEY_LEVEL = 'level';
    const KEY_MESSAGE = 'message';

    /**
     *
     * @var TableGateway
     */
    protected $dbTable;

    /**
     *
     * @param TableGateway $dbTable
     */
    public function __construct(TableGateway $dbTable)
    {
        $this->dbTable = $dbTable;
    }

    public function logWrite($id, $level, $message)
    {
        $logRecord = array(
            static::KEY_ID => $id,
            static::KEY_LEVEL => $level,
            static::KEY_MESSAGE => $message
        );
        try {
            $this->dbTable->insert($logRecord);
        } catch (\Exception $e) {
            throw new \RuntimeException('Can\'t write log ' . $id, 0, $e);
        }
    }

}
