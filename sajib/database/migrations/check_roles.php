<?php
require_once __DIR__ . '/../../config/app.php';

$sql = "SELECT DISTINCT role FROM users";
$result = mysqli_query($conn, $sql);

echo "<h3>Distinct Roles in 'users' table:</h3>";
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>" . ($row['role'] === NULL ? 'NULL' : htmlspecialchars($row['role'])) . "</li>";
}
echo "</ul>";

mysqli_close($conn);
?>
