<?php
require_once __DIR__ . '/../../includes/connection_file.php';

$result = mysqli_query($conn, "DESCRIBE users");

echo "<h3>Schema for 'users' table:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    foreach ($row as $val) {
        echo "<td>" . ($val === NULL ? 'NULL' : htmlspecialchars($val)) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?>
