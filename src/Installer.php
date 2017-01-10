<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 30.12.16
 * Time: 2:16 PM
 */

namespace zaboy\logger;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use zaboy\installer\Command;
use zaboy\installer\Install\InstallerInterface;

class Installer implements InstallerInterface
{
    const LOGS_DIR = 'logs';
    protected $ioComposer;
    const LOGS_FILE = 'logs.txt';

    /**
     * Installer constructor.
     * @param ContainerInterface $container
     * @param IOInterface $ioComposer
     */
    public function __construct(ContainerInterface $container, IOInterface $ioComposer)
    {
        $this->ioComposer = $ioComposer;
    }

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
            echo 'constant("APP_ENV") !== "dev" It has did nothing';
            exit;
        }
        $publicDir = Command::getPublicDir();
        if (file_exists($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR . DIRECTORY_SEPARATOR . self::LOGS_FILE)) {
            unlink($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR . DIRECTORY_SEPARATOR . self::LOGS_FILE);
        }
        if (is_dir($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR)) {
            rmdir($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR);
        }
    }

    /**
     * install
     * @return void
     */
    public function install()
    {
        if (constant('APP_ENV') !== 'dev') {
            echo 'constant("APP_ENV") !== "dev" It has did nothing';
            exit;
        }
        $publicDir = Command::getPublicDir();
        mkdir($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR);
        fopen($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR . DIRECTORY_SEPARATOR . self::LOGS_FILE, "w");
    }
}
