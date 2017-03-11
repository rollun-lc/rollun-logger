<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 30.12.16
 * Time: 2:16 PM
 */

namespace rollun\logger;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rollun\installer\Command;
use rollun\installer\Install\InstallerAbstract;
use rollun\logger\LogWriter\FileLogWriter;
use rollun\logger\LogWriter\FileLogWriterFactory;
use rollun\logger\LogWriter\LogWriterInterface;

class Installer extends InstallerAbstract
{
    const LOGS_DIR = 'logs';
    const LOGS_FILE = 'logs.csv';

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
        if (constant('APP_ENV') !== 'dev') {
            $this->consoleIO->write('constant("APP_ENV") !== "dev" It has did nothing');
        } else {
            $publicDir = Command::getDataDir();
            if (file_exists($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR . DIRECTORY_SEPARATOR . self::LOGS_FILE)) {
                unlink($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR . DIRECTORY_SEPARATOR . self::LOGS_FILE);
            }
            if (is_dir($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR)) {
                rmdir($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR);
            }
        }
    }

    /**
     * install
     * @return array
     */
    public function install()
    {
        if (constant('APP_ENV') !== 'dev') {
            $this->consoleIO->write('constant("APP_ENV") !== "dev" It has did nothing');
        } else {
            $dir = Command::getDataDir() . DIRECTORY_SEPARATOR . self::LOGS_DIR;
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $file = $dir . DIRECTORY_SEPARATOR . self::LOGS_FILE;
            fopen($file, "w");
            file_put_contents($file, "id;level;message\n");
            return [
                'dependencies' => [
                    'factories' => [
                        FileLogWriter::class => FileLogWriterFactory::class,
                        Logger::class => LoggerFactory::class,
                    ],
                    'aliases' => [
                        LogWriterInterface::DEFAULT_LOG_WRITER_SERVICE => FileLogWriter::class,
                        Logger::DEFAULT_LOGGER_SERVICE => Logger::class,
                    ]
                ]
            ];
        }
    }

    public function isInstall()
    {
        $publicDir = Command::getDataDir();
        $result = file_exists($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR . DIRECTORY_SEPARATOR . self::LOGS_FILE);
        $result &= $this->container->has(LogWriterInterface::DEFAULT_LOG_WRITER_SERVICE);
        $result &= $this->container->has(Logger::DEFAULT_LOGGER_SERVICE);
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
