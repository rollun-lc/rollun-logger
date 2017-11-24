<?php

namespace rollun\logger;

use rollun\installer\Command;
use rollun\installer\Install\InstallerAbstract;
use rollun\logger\Factory\LoggingErrorListenerDelegatorFactory;
use rollun\logger\LogWriter\Factory\DbLogWriterFactory;
use rollun\logger\LogWriter\DbLogWriter;
use rollun\logger\LogWriter\LogWriterInterface;
use rollun\utils\DbInstaller;
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
     * @param $logDbAdapter
     * @param $tableLogs
     * @return array
     * Checks if 'logs' table exists and if it has right column names
     *
     */
    protected function checkLogsTable($logDbAdapter, $tableLogs)
    {
        $result = [];
        $dbMetadata = Source\Factory::createSourceFromAdapter($logDbAdapter);
        $tableNames = $dbMetadata->getTableNames();
        $result["logsTableExist"] = in_array($tableLogs, $tableNames);
        $columnNames = $dbMetadata->getColumnNames($tableLogs);
        $rightColumnNames = ["id", "level", "message"];
        $result["rightColumnsExist"] = in_array($rightColumnNames[0], $columnNames, true)&&
            in_array($rightColumnNames[1], $columnNames, true)&&
            in_array($rightColumnNames[2], $columnNames, true);
        return $result;
    }

    /**
     * install
     * @return array
     * @throws \Exception
     */

    public function install()
    {
        if (constant('APP_ENV') !== 'dev') {
            $this->consoleIO->write('constant("APP_ENV") !== "dev" It has did nothing');
            return [];
        }
        //check existence of default DB adapter for DbLogWriter
        $logDbAdapterServiceMame = DbLogWriterFactory::LOG_DB_ADAPTER;
        $this->consoleIO->write('Standart service name for Logger`s Db adpter is ' . $logDbAdapterServiceMame);
        $logDbAdapterExist = $this->container->has($logDbAdapterServiceMame);
        //if standard adapter is not found, ask to specify existing adapter
        if (!$logDbAdapterExist) {
            $this->consoleIO->write('There is no standart service for Logger`s Db adpter', true);
            $paramName = 'db adapter';
            $question = 'Enter service name of Db adapter which you want to use for logs: ';
            $defaultValue = 'db';
            $logDbAdapterServiceMame = $this->askParamWithDefault($paramName, $question, $defaultValue);
            //check if Db adapter was created
            $logDbAdapterExist = $this->container->has($logDbAdapterServiceMame);
            if (!$logDbAdapterExist) {
                throw new \Exception("Db adapter \"$logDbAdapterServiceMame\" not found! Create it and run this installer again");
            }
        }
        $logDbAdapter = $this->container->get($logDbAdapterServiceMame);
        //check if retrieved adapter is actually a Db adapter
        if (!is_a($logDbAdapter, AdapterInterface::class, true)) {
            throw new \Exception($logDbAdapterServiceMame . " does not implement Zend\AdapterInterface");
        }
        $tableLogs = DbLogWriterFactory::LOG_TABLE_NAME;
        //check if 'logs' table exists
        $logsTableValid = $this->checkLogsTable($logDbAdapter, $tableLogs);
        if (!$logsTableValid["logsTableExist"]) {
            $question = "Table \"$tableLogs\" was not found." . PHP_EOL;
            $question .= "Create it with specific fields 'id', 'level' and 'message'." . PHP_EOL;
            $question .= "And type 'y' if you created the table or 'q' for quit" . PHP_EOL;
            $this->askYesNoQuit($question);
            //check if user created 'logs' table
            $logsTableValid = $this->checkLogsTable($logDbAdapter, $tableLogs);
            if (!$logsTableValid["logsTableExist"]) {
                throw new \Exception("Table \"$tableLogs\" not found!");
            }
        }
        //check if "logs" table has the right columns
        if (!$logsTableValid["rightColumnsExist"]) {
            $this->consoleIO->write("Table \"$tableLogs\" does not have specific fields 'id', 'level' and 'message'", true);
            $question = "Check your table and type 'y' to continue or 'q' for quit" . PHP_EOL;
            $this->askYesNoQuit($question);
            //check if user fixed the problem
            $logsTableValid = $this->checkLogsTable($logDbAdapter, $tableLogs);
            if (!$logsTableValid["rightColumnsExist"]) {
                throw new \Exception("Table \"$tableLogs\" does not have fields 'id', 'level' and 'message'");
            }
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
        $result = $this->container->has(Logger::DEFAULT_LOGGER_SERVICE);
        return $result;
    }

    public function getDependencyInstallers()
    {
        return [DbInstaller::class];
    }


    public function isDefaultOn()
    {
        return true;
    }

    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Предоставяляет обьект logger позволяющий писать логи в базу данных.\n" .
                    "Предоставяляет LoggerException, которое позволяет записывать в лог возникшее исключение, а так же предшествующее ему.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

}
