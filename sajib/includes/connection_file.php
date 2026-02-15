<?php
/**
 * Database Connection File
 * 
 * DEPRECATED: This file is maintained for backward compatibility only.
 * New code should use: require_once 'functions.php'; and call getConnection()
 * 
 * This file now uses the centralized function library.
 */

// Load centralized function library
require_once __DIR__ . '/functions.php';

// Create connection using library function (backward compatibility)
$conn = getConnection();

?>
