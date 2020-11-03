<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace ZendTest\Log;

use PHPUnit\Framework\TestCase;
use Zend\Log\Logger;
use Psr\Log\LogLevel;
use Psr\Container\ContainerInterface;
use Zend\Db\TableGateway\TableGateway;

class LoggerWithDbWriterTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->markTestIncomplete('ServiceNotFoundException : Unable to resolve service "logWithDbWriter" to a factory;');
        $this->logger = $this->getContainer()->get('logWithDbWriter');
        $this->getLogs();
    }

    /**
     * @return mixed|ContainerInterface
     */
    protected function getContainer()
    {
        if (is_null($this->container)) {
            $this->container = require 'config/container.php';
        }

        return $this->container;
    }

    public function getLogs()
    {
        $adapter = $this->container->get('logDbAdapter');
        $tableGateway = new TableGateway('logs', $adapter);
        $resultSet = $tableGateway->select();
        $tableGateway->delete(1);

        return $resultSet;
    }

    public function testLoggingArray()
    {
        $this->logger->log(LogLevel::INFO, 'test', ['lifecycle_token' => 'value1', 'context' => 'value2']);
        $resultSet = $this->getLogs();
        $rowArray = $resultSet->toArray();
        $row = $rowArray[0];
        $this->assertArraySubset(['level' => "info", 'priority' => "6", 'message' => "test"], $row);
    }
}
