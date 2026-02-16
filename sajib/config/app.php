<?php
// Define the root path of the project
define('ROOT_PATH', dirname(__DIR__));

// Configure global error handling for security
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/php_error.log');

// Define major directory paths
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('VENDOR_PATH', ROOT_PATH . '/vendor');
define('MAIN_PATH', ROOT_PATH . '/main');
define('MODULES_PATH', MAIN_PATH . '/modules');
define('ASSETS_PATH', MAIN_PATH . '/assets');

// Define URLs (dynamic detection)
$script_name = $_SERVER['SCRIPT_NAME'];
$main_pos = strpos($script_name, '/main');
$base_dir = substr($script_name, 0, $main_pos + 5); // Include '/main'
define('BASE_URL', $base_dir);
define('ASSETS_URL', BASE_URL . '/assets');

// Require the function library automatically
require_once INCLUDES_PATH . '/functions.php';

?>
