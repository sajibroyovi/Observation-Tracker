<?php
require_once __DIR__ . '/../../includes/connection_file.php';

$sql = "SELECT username, role FROM users";
$result = mysqli_query($conn, $sql);

echo "<h3>Users and Roles:</h3>";
echo "<table border='1'>";
echo "<tr><th>Username</th><th>Role</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . ($row['role'] === NULL ? 'NULL' : (empty($row['role']) ? '(empty string)' : htmlspecialchars($row['role']))) . "</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?>
