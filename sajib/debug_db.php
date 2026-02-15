<?php
require_once __DIR__ . '/includes/connection_file.php';

echo "<h1>Database Debug</h1>";

// Check tables
$result = mysqli_query($conn, "SHOW TABLES");
echo "<h2>Tables:</h2><ul>";
while ($row = mysqli_fetch_array($result)) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Check users
if (mysqli_query($conn, "DESCRIBE users")) {
    $result = mysqli_query($conn, "SELECT id, username, role FROM users");
    echo "<h2>Users:</h2><table border='1'><tr><th>ID</th><th>Username</th><th>Role</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['role']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<h2>Users table does NOT exist!</h2>";
}
?>
