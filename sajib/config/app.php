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
$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$main_pos = strpos($script_name, '/main/');

// Check for exact '/main' at the end of the script name
if ($main_pos === false && substr($script_name, -5) === '/main') {
    $main_pos = strlen($script_name) - 5;
}

if ($main_pos !== false) {
    $base_dir = substr($script_name, 0, $main_pos + 5); // Include '/main'
} else {
    // Fallback if /main is not found in the path (e.g. root is main or alias)
    $app_root = str_replace('\\', '/', ROOT_PATH);
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    if (!empty($doc_root) && strpos($app_root, $doc_root) === 0) {
        $base_dir = substr($app_root, strlen($doc_root)) . '/main';
    } else {
        // Ultimate fallback
        $base_dir = '/main'; 
    }
}

// Ensure no trailing slash and clean up double slashes
$base_dir = rtrim(preg_replace('#/+#', '/', $base_dir), '/');
if (empty($base_dir)) $base_dir = '';

define('BASE_URL', $base_dir);
define('ASSETS_URL', BASE_URL . '/assets');

// Require the function library automatically
require_once INCLUDES_PATH . '/functions.php';

?>
