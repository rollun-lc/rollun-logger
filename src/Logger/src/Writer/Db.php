<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-log for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace rollun\logger\Writer;

use rollun\logger\Exception\InvalidArgumentException;
use rollun\logger\Exception\RuntimeException;
use rollun\logger\Formatter\Db as DbFormatter;
use Traversable;
use Laminas\Db\Adapter\Adapter;

class Db extends AbstractWriter
{
    /**
     * Db adapter instance
     *
     * @var Adapter
     */
    protected $db;

    /**
     * Table name
     *
     * @var string
     */
    protected $tableName;

    /**
     * Relates database columns names to log data field keys.
     *
     * @var null|array
     */
    protected $columnMap;

    /**
     * Field separator for sub-elements
     *
     * @var string
     */
    protected $separator = '_';

    /**
     * Constructor
     *
     * We used the Adapter instead of Zend\Db for a performance reason.
     *
     * @param Adapter|array|Traversable $db
     * @param string|null $tableName
     * @param array|null $columnMap
     * @param string|null $separator
     * @throws InvalidArgumentException
     */
    public function __construct($db, $tableName = null, array $columnMap = null, $separator = null)
    {
        if ($db instanceof Traversable) {
            $db = iterator_to_array($db);
        }

        if (is_array($db)) {
            parent::__construct($db);
            $separator = $db['separator'] ?? null;
            $columnMap = $db['column'] ?? null;
            $tableName = $db['table'] ?? null;
            $db = $db['db'] ?? null;
        }

        if (!$db instanceof Adapter) {
            throw new InvalidArgumentException('You must pass a valid Zend\Db\Adapter\Adapter');
        }

        $tableName = (string) $tableName;
        if ('' === $tableName) {
            throw new InvalidArgumentException(
                'You must specify a table name. Either directly in the constructor, or via options'
            );
        }

        $this->db = $db;
        $this->tableName = $tableName;
        $this->columnMap = $columnMap;

        if (!empty($separator)) {
            $this->separator = $separator;
        }

        if (!$this->hasFormatter()) {
            $this->setFormatter(new DbFormatter());
        }
    }

    /**
     * Remove reference to database adapter
     *
     * @return void
     */
    public function shutdown()
    {
        $this->db = null;
    }

    /**
     * Write a message to the log.
     *
     * @param array $event event data
     * @return void
     * @throws RuntimeException
     */
    protected function doWrite(array $event)
    {
        if (null === $this->db) {
            throw new RuntimeException('Database adapter is null');
        }

        $event = $this->formatter->format($event);

        // Transform the event array into fields
        if (null === $this->columnMap) {
            $dataToInsert = $this->eventIntoColumn($event);
        } else {
            $dataToInsert = $this->mapEventIntoColumn($event, $this->columnMap);
        }

        $statement = $this->db->query($this->prepareInsert($dataToInsert));
        $statement->execute($dataToInsert);
    }

    /**
     * Prepare the INSERT SQL statement
     *
     * @param  array $fields
     * @return string
     */
    protected function prepareInsert(array $fields)
    {
        $keys = array_keys($fields);
        $sql = 'INSERT INTO ' . $this->db->platform->quoteIdentifier($this->tableName) . ' (' .
                implode(",", array_map([$this->db->platform, 'quoteIdentifier'], $keys)) . ') VALUES (' .
                implode(",", array_map([$this->db->driver, 'formatParameterName'], $keys)) . ')';

        return $sql;
    }

    /**
     * Map event into column using the $columnMap array
     *
     * @param  array $event
     * @param  array $columnMap
     * @return array
     */
    protected function mapEventIntoColumn(array $event, array $columnMap = null)
    {
        if (empty($event)) {
            return [];
        }

        $data = [];
        foreach ($event as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $subValue) {
                    if (is_array($columnMap[$name]) && isset($columnMap[$name][$key])) {
                        if (is_scalar($subValue)) {
                            $data[$columnMap[$name][$key]] = $subValue;
                            continue;
                        }

                        $data[$columnMap[$name][$key]] = var_export($subValue, true);
                    }
                }
            } elseif (isset($columnMap[$name])) {
                $data[$columnMap[$name]] = $value;
            }
        }
        return $data;
    }

    /**
     * Transform event into column for the db table
     *
     * @param  array $event
     * @return array
     */
    protected function eventIntoColumn(array $event)
    {
        if (empty($event)) {
            return [];
        }

        $data = [];
        foreach ($event as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $subvalue) {
                    if (is_scalar($subvalue)) {
                        $data[$name . $this->separator . $key] = $subvalue;
                        continue;
                    }

                    $data[$name . $this->separator . $key] = var_export($subvalue, true);
                }
            } else {
                $data[$name] = $value;
            }
        }
        return $data;
    }

}
