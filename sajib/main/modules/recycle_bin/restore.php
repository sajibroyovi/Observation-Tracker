<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';

// Only Super Admins can restore
if (!isSuperAdmin()) {
    $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
    showError('Unauthorized access.');
    redirectTo($redirect);
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // CSRF check
    if (isset($_GET['csrf_token']) && !validateCsrfToken($_GET['csrf_token'])) {
        log_error("CSRF token validation failed for restore attempt on recycle bin", ['id' => $id]);
        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
        showError('Security Validation Failed: CSRF Token Mismatch.');
        redirectTo($redirect);
        exit;
    }

    // 1. Fetch the record from recycle bin
    $stmt = mysqli_prepare($conn, "SELECT table_name, data_payload FROM recycle_bin WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            $table_name = $row['table_name'];
            $payload = json_decode($row['data_payload'], true);
            mysqli_stmt_close($stmt);

            if ($payload && is_array($payload)) {
                // 2. Build Dynamic Insert Query
                $columns = array_keys($payload);
                $placeholders = array_fill(0, count($payload), '?');
                $values = array_values($payload);
                
                $sql = "INSERT INTO `" . $table_name . "` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $placeholders) . ")";
                
                $insert_stmt = mysqli_prepare($conn, $sql);
                if ($insert_stmt) {
                    $types = '';
                    foreach ($values as $val) {
                         if (is_int($val)) { $types .= 'i'; }
                         elseif (is_float($val)) { $types .= 'd'; }
                         else { $types .= 's'; }
                    }
                    
                    mysqli_stmt_bind_param($insert_stmt, $types, ...$values);
                    if (mysqli_stmt_execute($insert_stmt)) {
                        // 3. Setup successful, delete from recycle bin
                        $del_stmt = mysqli_prepare($conn, "DELETE FROM recycle_bin WHERE id = ?");
                        if ($del_stmt) {
                            mysqli_stmt_bind_param($del_stmt, "i", $id);
                            mysqli_stmt_execute($del_stmt);
                            mysqli_stmt_close($del_stmt);
                        }
                        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
                        showSuccess('Record successfully restored to ' . htmlspecialchars($table_name));
                        redirectTo($redirect);
                    } else {
                        log_error("Failed to restore record in target table", ['table' => $table_name, 'error' => mysqli_stmt_error($insert_stmt)]);
                        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
                        showError('Failed to restore record.');
                        redirectTo($redirect);
                    }
                    mysqli_stmt_close($insert_stmt);
                } else {
                    log_error("Failed to prepare restore query", ['table' => $table_name, 'error' => mysqli_error($conn)]);
                    $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
                    showError('Internal Server Error.');
                    redirectTo($redirect);
                }
            } else {
                log_error("Failed to decode JSON payload for restore", ['id' => $id]);
                $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
                showError('Data corruption: Failed to decode payload.');
                redirectTo($redirect);
            }
        } else {
            $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
            showError('Record not found.');
            if ($stmt) mysqli_stmt_close($stmt);
            redirectTo($redirect);
        }
    } else {
        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
        showError('Internal Server Error.');
        redirectTo($redirect);
    }
} else {
    $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
    showError('Invalid Record ID.');
    redirectTo($redirect);
}

mysqli_close($conn);
exit;
