<?php
/**
 * Database Configuration
 */
return [
    'servername' => getenv('DB_HOST') ?: 'localhost',
    'username'   => getenv('DB_USER') ?: 'root',
    'password'   => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'dbname'     => getenv('DB_NAME') ?: 'shift_hand_over'
];
