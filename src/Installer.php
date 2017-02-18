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
            $this->io->write('constant("APP_ENV") !== "dev" It has did nothing');
            exit;
        }
        $publicDir = Command::getDataDir();
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
        $dir = Command::getDataDir() . DIRECTORY_SEPARATOR . self::LOGS_DIR;
        if(!is_dir($dir)) {
            mkdir($dir,0777,true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . self::LOGS_FILE;
        fopen($file, "w");
        file_put_contents($file, "id;level;message\n");
    }
}
