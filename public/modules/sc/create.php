<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';  
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for security mail create attempt");
        header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Security Validation Failed: CSRF Token Mismatch."));
        exit;
    }

    // Get form data
        $subject_line = cleanInput($_POST['subject_line']);
        $priority = cleanInput($_POST['priority']);
        $status = cleanInput($_POST['status']);
        $created_by = $_SESSION['username'];

        // Insert into security_mail using prepared statements
        $sql = "INSERT INTO security_mail (subject_line, priority, status, created_by) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $subject_line, $priority, $status, $created_by);
            if (mysqli_stmt_execute($stmt)) { 
                header('Location: ' . BASE_URL . '/index.php?status=success&msg=' . urlencode('Security Record inserted successfully'));
            } else { 
                log_error("Failed to insert security mail record", ['error' => mysqli_stmt_error($stmt)]);
                header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Critical Error: Failed to save record."));
            }
            mysqli_stmt_close($stmt);
        } else {
            log_error("Failed to prepare statement for security mail", ['error' => mysqli_error($conn)]);
            header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Critical Error: Internal Server Error."));
        }
        mysqli_close($conn);
        exit();
    }

  ?>
