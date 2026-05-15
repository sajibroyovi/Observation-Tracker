<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';  
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for promo banner create attempt");
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/');
    }

    // Get form data
        $subject_line = cleanInput($_POST['subject_line']); 
        $status = cleanInput($_POST['status']);
        $start_date = cleanInput($_POST['start_date']);
        $created_by = $_SESSION['username'];
        $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
        $handover_date = cleanInput($_POST['handover_date'] ?? null);

        // Insert into promo_banner using prepared statement
        $sql = "INSERT INTO promo_banner (subject_line, status, start_time, created_by, handed_over_to, handover_date) VALUES (?, ?, ?, ?, ?, ?)"; 
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssss", $subject_line, $status, $start_date, $created_by, $handed_over_to, $handover_date);
            if (mysqli_stmt_execute($stmt)) { 
                $redirect = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
                showSuccess('Promo Banner record inserted successfully');
                redirectTo($redirect);
            } else {
                log_error("Failed to insert promo banner record", ['error' => mysqli_stmt_error($stmt)]);
                $redirect = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
                showError("Critical Error: Failed to save record.");
                redirectTo($redirect);
            }
            mysqli_stmt_close($stmt);
        } else {
            log_error("Failed to prepare statement for promo banner", ['error' => mysqli_error($conn)]);
            showError("Critical Error: Internal Server Error.");
        redirectTo(BASE_URL . '/');
        }
        mysqli_close($conn);
        exit();
    }

   ?>
