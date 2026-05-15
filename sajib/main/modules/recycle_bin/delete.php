<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';

// Only Super Admins can hard delete
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
        log_error("CSRF token validation failed for delete attempt on recycle bin", ['id' => $id]);
        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
        showError('Security Validation Failed: CSRF Token Mismatch.');
        redirectTo($redirect);
        exit;
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

            // Identify potential image/file fields based on common patterns
            $image_fields = ['l1_image', 'l1_image_2', 'image_path', 'file_path', 'attachment'];
            
            // Add module-specific fields if necessary
            if ($table_name === 'observations') {
                $image_fields = array_unique(array_merge($image_fields, ['l1_image', 'l1_image_2']));
            }
            
            // Delete files if they exist
            foreach ($image_fields as $field) {
                if (!empty($payload[$field]) && is_string($payload[$field])) {
                    // Normalize the path and check if it's within the uploads directory or relative to assets
                    $path_val = $payload[$field];
                    
                    // We expect paths like "uploads/filename.jpg"
                    // Construct the absolute path
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
            $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
            showSuccess('Record and associated files deleted permanently');
            redirectTo($redirect);
        } else {
            log_error("Failed to delete record from recycle_bin", ['id' => $id, 'error' => mysqli_stmt_error($stmt)]);
            $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
            showError('Critical Error: Failed to process request.');
            redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare delete statement for recycle_bin", ['error' => mysqli_error($conn)]);
        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
            showError('Critical Error: Failed to process request.');
            redirectTo($redirect);
    }
} else {
    $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
    showError('Error: Invalid record ID.');
    redirectTo($redirect);
}

mysqli_close($conn);
exit;
