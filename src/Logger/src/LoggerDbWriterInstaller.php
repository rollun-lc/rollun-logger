<?php

namespace rollun\logger;

use rollun\installer\Command;
use rollun\installer\Install\InstallerAbstract;
use rollun\logger\Factory\LoggingErrorListenerDelegatorFactory;
use rollun\logger\LogWriter\Factory\DbLogWriterFactory;
use rollun\logger\LogWriter\DbLogWriter;
use rollun\logger\LogWriter\LogWriterInterface;
use Zend\Stratigility\Middleware\ErrorHandler;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Metadata\Source;

class LoggerDbWriterInstaller extends InstallerAbstract
{

    /**
     * Make clean and install.
     * @return void
     */
    public function reinstall()
    {
        $this->uninstall();
        $this->install();
    }

    /**
     * Clean all installation
     * @return void
     */
    public function uninstall()
    {

    }

    /**
     * install
     * @return array
     */
    public function install()
    {
        if (constant('APP_ENV') !== 'dev') {
            $this->consoleIO->write('constant("APP_ENV") !== "dev" It has did nothing');
            return;
        }
        $this->consoleIO->write('Standart service name for logger db adpter is '
                . DbLogWriterFactory::LOG_DB_ADAPTER
        );
        $logDbAdapterExist = $this->container->has(DbLogWriterFactory::LOG_DB_ADAPTER);
        if (!$logDbAdapterExist) {

            $this->consoleIO->write('There is no standart service for logger db adpter', true);
            $paramName = 'db adapter';
            $question = 'Enter service name of db adapter for logs: ';
            $defaultValue = 'db';
            $logDbAdapterServiceMame = $this->askParamWithDefault($paramName, $question, $defaultValue);
            $logDbAdapterExist = $this->container->has($logDbAdapterServiceMame);
            if (!$logDbAdapterExist) {
                $this->consoleIO->writeError('db adapter for logger not found!');
                $this->consoleIO->writeError('Create it and run this installer again', true);
                return;
            }
        }

        $logDbAdapter = $this->container->get($logDbAdapterServiceMame);
        if (!is_a($logDbAdapter, AdapterInterface::class, true)) {
            $this->consoleIO->writeError($logDbAdapterServiceMame . ' is not db adapter');
            return;
        }

        $dbMetadata = Source\Factory::createSourceFromAdapter($logDbAdapter);
        $tableNames = $dbMetadata->getTableNames();
        $tableLogs = DbLogWriterFactory::LOG_TABLE_NAME;
        $logsTableExist = in_array($tableLogs, $tableNames);
        if (!$logsTableExist) {
            $question = "There is no table \"$tableLogs\" " . PHP_EOL;
            $question .= "Create it with filds 'id', 'level' and 'message'" . PHP_EOL;
            $question .= "And type 'y' or 'q' for quit" . PHP_EOL;
            $this->askYesNoQuit($question);
            $tableNames = $dbMetadata->getTableNames();
            $tableLogs = DbLogWriterFactory::LOG_TABLE_NAME;
            $logsTableExist = in_array($tableLogs, $tableNames);
            $this->consoleIO->writeError("table \"$tableLogs\" not found!");
            return;
        }

        return [
            'dependencies' => [
                'factories' => [
                    DbLogWriter::class => DbLogWriterFactory::class,
                    Logger::class => LoggerFactory::class,
                ],
                'aliases' => [
                    LogWriterInterface::DEFAULT_LOG_WRITER_SERVICE => DbLogWriter::class,
                    Logger::DEFAULT_LOGGER_SERVICE => Logger::class,
                ],
                'delegators' => [
                    ErrorHandler::class => [
                        LoggingErrorListenerDelegatorFactory::class
                    ]
                ]
            ]
        ];
    }

    public function isInstall()
    {
        $result = $this->container->has(LogWriterInterface::DEFAULT_LOG_WRITER_SERVICE);
        return $result;
    }

    public function isDefaultOn()
    {
        return true;
    }

    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Предоставяляет обьект logger позволяющий писать сообщения в лог.\n" .
                        "LoggerException которое позволяет записывать в лог возникшее исключение, а так же предшествующее ему.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

}
