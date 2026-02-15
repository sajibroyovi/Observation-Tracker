<?php
/**
 * Application Path Constants
 */

// Define the root path of the project
define('ROOT_PATH', dirname(__DIR__));

// Define major directory paths
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('VENDOR_PATH', ROOT_PATH . '/vendor');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('MODULES_PATH', PUBLIC_PATH . '/modules');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');

// Define URLs (dynamic detection)
$script_name = $_SERVER['SCRIPT_NAME'];
$public_pos = strpos($script_name, '/public');
$base_dir = substr($script_name, 0, $public_pos + 7); // Include '/public'
define('BASE_URL', $base_dir);
define('ASSETS_URL', BASE_URL . '/assets');

// Require the function library automatically
require_once INCLUDES_PATH . '/functions.php';

?>
