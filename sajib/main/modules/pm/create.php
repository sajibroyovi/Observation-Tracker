<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for pending mail create attempt");
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/');
    }

    // Get form data
    $subject_lines = $_POST['subject_lines'];
    $priority = cleanInput($_POST['priority']);
    $status = cleanInput($_POST['status']);
    $created_by = $_SESSION['username'];
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);

    // Split subject lines by newline
    $lines = explode("\n", $subject_lines);
    
    $inserted_count = 0;
    
    // Prepare statement once for bulk insert
    $sql = "INSERT INTO pending_mail (subject_line, priority, status, created_by, handed_over_to, handover_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        foreach($lines as $line) {
            $line = trim($line);
            if(!empty($line)) {
                $line_cleaned = cleanInput($line);
                mysqli_stmt_bind_param($stmt, "ssssss", $line_cleaned, $priority, $status, $created_by, $handed_over_to, $handover_date);
                if (mysqli_stmt_execute($stmt)) {
                    $inserted_count++;
                } else {
                    log_error("Failed to insert pending mail line", ['error' => mysqli_stmt_error($stmt)]);
                }
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for pending mail", ['error' => mysqli_error($conn)]);
    }

    if ($inserted_count > 0) {
        $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
        showSuccess('Pending Mail record(s) inserted successfully');
        redirectTo($redirect);
    } else {
        $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
        showError("Failed to insert any records.");
        redirectTo($redirect);
    }

    exit();
}
?>
