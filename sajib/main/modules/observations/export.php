<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Observations_Report_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch data securely
$sql = "SELECT * FROM observations ORDER BY serial_no DESC";
$stmt = executePreparedStatement($conn, $sql);
$result = $stmt ? mysqli_stmt_get_result($stmt) : false;

// Construct base URL for images securely
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
// Sanitize host and use it cautiously to prevent host-based XSS
$host = htmlspecialchars($_SERVER['HTTP_HOST'] ?? '', ENT_QUOTES, 'UTF-8');
$script_name = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8');
$currentPath = str_replace('\\', '/', dirname($script_name));
$baseDir = dirname($currentPath); 
$baseUrl = $protocol . "://" . $host . $baseDir . "/";

echo '<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; vertical-align: middle; }
        .header { background-color: #17a2b8; color: white; font-weight: bold; }
        .text-left { text-align: left; }
        img { display: block; margin: 0 auto; }
    </style>
</head>
<body>';

echo '<table>';
echo '<thead>
        <tr class="header">
            <th width="50">#</th>
            <th width="150">Observation Name</th>
            <th width="120">Date</th>
            <th width="200">L1 Observation</th>
            <th width="100">L1 By</th>
            <th width="200">L2 Observation</th>
            <th width="100">L2 By</th>
            <th width="210">Image 1</th>
            <th width="210">Image 2</th>
        </tr>
      </thead>';

echo '<tbody>';
$count = 1;
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . (int)$count . '</td>';
        echo '<td class="text-left">' . e($row['observation_names']) . '</td>';
        echo '<td>' . e(date('d M, Y', strtotime($row['start_date']))) . '</td>';
        echo '<td class="text-left">' . e($row['l1_observation']) . '</td>';
        echo '<td>' . e($row['l1_observations_by']) . '</td>';
        echo '<td class="text-left">' . e($row['l2_observation'] ?? '-') . '</td>';
        echo '<td>' . e($row['l2_observations_by'] ?? '-') . '</td>';
        
        // Image 1
        echo '<td width="210" height="210" align="center" valign="middle">';
        if (!empty($row['l1_image_1'])) {
            $imageUrl1 = $baseUrl . str_replace(' ', '%20', $row['l1_image_1']);
            echo '<img src="' . e($imageUrl1) . '" width="150" height="150">';
        } else {
            echo 'No Image';
        }
        echo '</td>';
        
        // Image 2
        echo '<td width="210" height="210" align="center" valign="middle">';
        if (!empty($row['l1_image_2'])) {
            $imageUrl2 = $baseUrl . str_replace(' ', '%20', $row['l1_image_2']);
            echo '<img src="' . e($imageUrl2) . '" width="150" height="150">';
        } else {
            echo 'No Image';
        }
        echo '</td>';
        
        echo '</tr>';
        $count++;
    }
}
if ($stmt) mysqli_stmt_close($stmt);

echo '</tbody>';
echo '</table>';
echo '</body></html>';

mysqli_close($conn);
?>
