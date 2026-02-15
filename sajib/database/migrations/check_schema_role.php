<?php
require_once __DIR__ . '/../../config/app.php';
$result = mysqli_query($conn, "DESCRIBE users");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
