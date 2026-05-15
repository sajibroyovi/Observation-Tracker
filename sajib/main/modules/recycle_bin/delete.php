<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

// Only Super Admins can hard delete
if (!isSuperAdmin()) {
    $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
    showError('Unauthorized access.');
    redirectTo($redirect);
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // CSRF check
    if (isset($_GET['csrf_token']) && !validateCsrfToken($_GET['csrf_token'])) {
        log_error("CSRF token validation failed for delete attempt on recycle bin", ['id' => $id]);
        $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
        showError('Security Validation Failed: CSRF Token Mismatch.');
        redirectTo($redirect);
    }

    // Fetch the record first to get the data payload for file cleanup
    $fetch_sql = "SELECT table_name, data_payload FROM recycle_bin WHERE id = ?";
    $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
    
    if ($fetch_stmt) {
        mysqli_stmt_bind_param($fetch_stmt, "i", $id);
        mysqli_stmt_execute($fetch_stmt);
        $res = mysqli_stmt_get_result($fetch_stmt);
        $record = mysqli_fetch_assoc($res);
        mysqli_stmt_close($fetch_stmt);

        if ($record) {
            $payload = json_decode($record['data_payload'], true);
            $table_name = $record['table_name'];

            // Identify potential image/file fields
            $image_fields = ['l1_image', 'l1_image_2', 'image_path', 'file_path', 'attachment'];
            
            // Delete files if they exist
            foreach ($image_fields as $field) {
                if (!empty($payload[$field]) && is_string($payload[$field])) {
                    $path_val = $payload[$field];
                    $file_path = ASSETS_PATH . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path_val), DIRECTORY_SEPARATOR);
                    
                    if (file_exists($file_path) && is_file($file_path)) {
                        if (!unlink($file_path)) {
                            log_error("Failed to delete file from drive during recycle bin cleanup", ['path' => $file_path, 'module' => $table_name]);
                        }
                    }
                }
            }
        }
    }

    $sql = "DELETE FROM recycle_bin WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
            showSuccess('Record and associated files deleted permanently');
            redirectTo($redirect);
        } else {
            log_error("Failed to delete record from recycle_bin", ['id' => $id, 'error' => mysqli_stmt_error($stmt)]);
            $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
            showError('Critical Error: Failed to process request.');
            redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare delete statement for recycle_bin", ['error' => mysqli_error($conn)]);
        $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
        showError('Critical Error: Failed to process request.');
        redirectTo($redirect);
    }
} else {
    $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
    showError('Error: Invalid record ID.');
    redirectTo($redirect);
}

mysqli_close($conn);
exit;
?>
