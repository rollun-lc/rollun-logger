<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\logger;

use PHPUnit\Framework\TestCase;
use rollun\logger\LifeCycleToken;
use rollun\logger\Logger;
use Psr\Log\LogLevel;
use Psr\Container\ContainerInterface;
use rollun\logger\Processor\LifeCycleTokenInjector;
use Laminas\Db\TableGateway\TableGateway;

class LoggerWithDbWriterTest extends TestCase
{
    const LIFECYCLE_TOKEN = 'token';

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
    public function setUp(): void

    {
        $this->markTestIncomplete("TODO: Automatic apply log table migration.");
        $this->logger = $this->getContainer()->get('logWithDbWriter');
        $this->logger->addProcessor(new LifeCycleTokenInjector(new LifeCycleToken(self::LIFECYCLE_TOKEN)));
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
        $this->logger->log(LogLevel::INFO, 'test', ['context' => 'value']);
        $resultSet = $this->getLogs();
        $rowArray = $resultSet->toArray();
        $row = $rowArray[0];
        $this->assertArraySubset([
            'level' => 'info',
            'priority' => '6',
            'message' => 'test',
            'lifecycle_token' => self::LIFECYCLE_TOKEN,
            'context' => '{"context":"value"}'
        ], $row);
    }
}
