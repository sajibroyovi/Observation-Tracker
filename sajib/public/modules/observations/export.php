<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Observations_Report_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch data
$sql = "SELECT * FROM observations ORDER BY serial_no DESC";
$result = mysqli_query($conn, $sql);

// Construct base URL for images
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Get the directory path and replace spaces for URL
$currentPath = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$baseDir = dirname($currentPath); // Go up one level from exportdata
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
    } else {
        echo 'No Image';
    }
    echo '</td>';
    
    // Image 2
    echo '<td width="210" height="210" align="center" valign="middle">';
    if (!empty($row['l1_image_2'])) {
        $imageUrl2 = $baseUrl . str_replace(' ', '%20', $row['l1_image_2']);
        echo '<img src="' . $imageUrl2 . '" width="150" height="150">';
    } else {
        echo 'No Image';
    }
    echo '</td>';
    
    echo '</tr>';
    $count++;
}

echo '</tbody>';
echo '</table>';
echo '</body></html>';

mysqli_close($conn);
?>




