<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for service enable/disable create attempt");
        header('Location: ' . BASE_URL . '/?status=error&msg=' . urlencode("Security Validation Failed: CSRF Token Mismatch."));
        exit;
    }

    // Get form data
        $service_name = cleanInput($_POST['service_name']); 
        $action_date = cleanInput($_POST['action_date']);
        $action_taken = cleanInput($_POST['action_taken']);
        $action_taken_by = cleanInput($_POST['action_taken_by']); 
        $reference = cleanInput($_POST['reference']);
        $created_by = $_SESSION['username'];

        // Insert into enable_disable table using prepared statement
        $sql = "INSERT INTO enable_disable (service_name, action_date, action_taken, action_taken_by, reference, created_by) VALUES (?, ?, ?, ?, ?, ?)"; 
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssss", $service_name, $action_date, $action_taken, $action_taken_by, $reference, $created_by);
            if (mysqli_stmt_execute($stmt)) { 
                header('Location: ' . BASE_URL . '/?status=success&msg=' . urlencode('Service Enable/Disable record inserted successfully'));
            } else {
                log_error("Failed to insert service enable/disable record", ['error' => mysqli_stmt_error($stmt)]);
                header('Location: ' . BASE_URL . '/?status=error&msg=' . urlencode("Critical Error: Failed to save record."));
            }
            mysqli_stmt_close($stmt);
        } else {
            log_error("Failed to prepare statement for service enable/disable", ['error' => mysqli_error($conn)]);
            header('Location: ' . BASE_URL . '/?status=error&msg=' . urlencode("Critical Error: Internal Server Error."));
        }
        mysqli_close($conn);
        exit();
    }
?>
