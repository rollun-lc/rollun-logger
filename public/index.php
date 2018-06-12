<?php

// Delegate static file requests back to the PHP built-in webserver
use Interop\Container\Exception\ContainerException;

if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
require_once 'config/env_configurator.php';
/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
call_user_func(function () {
    if (!function_exists('get_all_headers')) {
        function get_all_headers()
        {
            $arh = array();
            $rx_http = '/\AHTTP_/';
            foreach ($_SERVER as $key => $val) {
                if (preg_match($rx_http, $key)) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    // do some nasty string manipulations to restore the original letter case
                    // this should work in most cases
                    $rx_matches = explode('_', $arh_key);
                    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                        foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
            return ($arh);
        }
    }
    //init lifecycle token
    $lifeCycleToken = \rollun\logger\LifeCycleToken::generateToken();
    if (get_all_headers() && array_key_exists("LifeCycleToken", get_all_headers())) {
        $lifeCycleToken->unserialize(get_all_headers()["LifeCycleToken"]);
    }
    /** use container method to set service.*/
    /** @var \Interop\Container\ContainerInterface $container */
    $container = require "config/container.php";
    $container->setService(\rollun\logger\LifeCycleToken::class, $lifeCycleToken);

    try {
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
    } catch (ContainerException $containerException) {
        $logger = new \rollun\logger\SimpleLogger();
        $logger->error($containerException);
        $container->setService(\Psr\Log\LoggerInterface::class, $logger);
    }


    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $logger->notice("Test notice. %request_time", ["request_time" => $_SERVER["REQUEST_TIME"]]);
});
