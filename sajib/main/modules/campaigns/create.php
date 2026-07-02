<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for campaign create attempt");
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/');
    }

    // Get form data
    $campaign_names = $_POST['campaign_names'];
    $start_date = cleanInput($_POST['start_date']);
    $status = cleanInput($_POST['status']);
    $description = cleanInput($_POST['description']);
    $created_by = $_SESSION['username'];
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);

    // Split campaign names by newline
    $names = explode("\n", $campaign_names);
    
    $inserted_count = 0;
    
    // Prepare statement once for bulk insert
    $sql = "INSERT INTO campaign (campaign_name, start_date, status, description, created_by, handed_over_to, handover_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        foreach($names as $name) {
            $name = trim($name);
            if(!empty($name)) {
                $name_cleaned = cleanInput($name);
                mysqli_stmt_bind_param($stmt, "sssssss", $name_cleaned, $start_date, $status, $description, $created_by, $handed_over_to, $handover_date);
                if (mysqli_stmt_execute($stmt)) {
                    $inserted_count++;
                } else {
                    log_error("Failed to insert campaign line", ['error' => mysqli_stmt_error($stmt)]);
                }
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for campaign", ['error' => mysqli_error($conn)]);
    }

    if ($inserted_count > 0) {
        $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
        showSuccess($inserted_count . ' Campaign record(s) inserted successfully');
        redirectTo($redirect);
    } else {
        $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
        showError("Failed to insert any records.");
        redirectTo($redirect);
    }

    exit();
}
?>
