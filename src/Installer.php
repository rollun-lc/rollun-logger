<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 30.12.16
 * Time: 2:16 PM
 */

namespace rolluncom\logger;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rolluncom\installer\Command;
use rolluncom\installer\Install\InstallerAbstract;

class Installer extends InstallerAbstract
{
    const LOGS_DIR = 'logs';
    const LOGS_FILE = 'logs.txt';

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
            $this->io->write('constant("APP_ENV") !== "dev" It has did nothing');
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
            $this->io->write('constant("APP_ENV") !== "dev" It has did nothing');
            exit;
        }
        $publicDir = Command::getPublicDir();
        mkdir($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR);
        fopen($publicDir . DIRECTORY_SEPARATOR . self::LOGS_DIR . DIRECTORY_SEPARATOR . self::LOGS_FILE, "w");
    }
}
