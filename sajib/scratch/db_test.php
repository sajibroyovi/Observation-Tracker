<?php
$config = require 'c:/xampp/htdocs/sajib/sajib/config/database.php';
$conn = @mysqli_connect($config['servername'], $config['username'], $config['password'], $config['dbname']);
if (!$conn) {
    echo 'Failed to connect: ' . mysqli_connect_error();
} else {
    echo 'Connected successfully';
    mysqli_close($conn);
}
