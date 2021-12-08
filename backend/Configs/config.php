<?php

/**
 * CONFIG FILE
 */


ini_set("display_errors", true);
error_reporting(E_ERROR);

define("APP_PATH",    "http://172.16.50.52");
define("APP_IP",      "172.16.50.52");
define("APP_PORT",    "8080");

require_once "sql.config.php";
require_once "am.config.php";

session_start();

spl_autoload_register(function($class) {
    require_once str_replace('\\', '/', $class) . '.class.php';
  });

?>