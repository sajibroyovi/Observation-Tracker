<?php
require_once __DIR__ . '/../config/app.php';
initSession();
session_destroy();
redirectTo('login');
?>
