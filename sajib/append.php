<?php
$functionCode = <<<EOT

/**
 * Send an email notification to an assigned technician
 * 
 * @param mysqli \$conn Database connection
 * @param string \$technician_username The username of the assigned technician
 * @param string \$observation_name The title of the observation
 * @return bool True if successful, false otherwise
 */
function sendAssignmentEmail(\$conn, \$technician_username, \$observation_name) {
    if (!\$conn || empty(\$technician_username)) return false;

    // 1. Fetch technician email
    \$stmt = mysqli_prepare(\$conn, "SELECT email FROM users WHERE username = ?");
    if (!\$stmt) return false;
    
    mysqli_stmt_bind_param(\$stmt, "s", \$technician_username);
    mysqli_stmt_execute(\$stmt);
    \$result = mysqli_stmt_get_result(\$stmt);
    
    \$email = '';
    if (\$result && mysqli_num_rows(\$result) > 0) {
        \$row = mysqli_fetch_assoc(\$result);
        \$email = \$row['email'];
        if (empty(\$email)) return false; // No email to send to
    } else {
        return false;
    }
    mysqli_stmt_close(\$stmt);

    // 2. Prepare PHPMailer securely
    require_once ROOT_PATH . '/vendor/PHPMailer-6.8.0/src/Exception.php';
    require_once ROOT_PATH . '/vendor/PHPMailer-6.8.0/src/PHPMailer.php';
    require_once ROOT_PATH . '/vendor/PHPMailer-6.8.0/src/SMTP.php';

    \$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        \$mail->isSMTP();
        \$mail->Host = 'smtp.gmail.com';
        \$mail->SMTPAuth = true;
        // Sending from the original system sender
        \$mail->Username = 'impsajibroy@gmail.com'; 
        \$mail->Password = 'mbxs mvsy fkkg hagm'; 
        \$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        \$mail->Port = 587;

        // Recipients
        \$mail->setFrom('impsajibroy@gmail.com', 'Shift Handover System');
        \$mail->addAddress(\$email);

        // Content
        \$mail->isHTML(true);
        \$mail->Subject = "New Observation Assigned: " . htmlspecialchars(\$observation_name);
        
        \$message = "<html><head><style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
            h2 { color: #D12053; }
            .button { display: inline-block; padding: 10px 20px; background-color: #D12053; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        </style></head><body>";
        \$message .= "<div class='container'>";
        \$message .= "<h2>Observation Assigned</h2>";
        \$message .= "<p>Hello <strong>" . htmlspecialchars(\$technician_username) . "</strong>,</p>";
        \$message .= "<p>An observation has been assigned to you by " . htmlspecialchars(\$_SESSION['username'] ?? 'the system') . ".</p>";
        \$message .= "<ul>";
        \$message .= "<li><strong>Observation:</strong> " . htmlspecialchars(\$observation_name) . "</li>";
        \$message .= "<li><strong>Assigned At:</strong> " . date('Y-m-d H:i') . "</li>";
        \$message .= "</ul>";
        \$message .= "<p>Please log in to the Shift Handover System to review the details and provide an update.</p>";
        \$message .= "<a href='" . BASE_URL . "/modules/observations/view' class='button'>View Observations</a>";
        \$message .= "<p style='margin-top: 30px; font-size: 11px; color: #777;'>This is an automated notification. Please do not reply directly to this email.</p>";
        \$message .= "</div></body></html>";

        \$mail->Body = \$message;
        \$mail->send();
        return true;
    } catch (\Exception \$e) {
        log_error("Failed to send assignment email", ['email' => \$email, 'error' => \$mail->ErrorInfo]);
        return false;
    }
}
EOT;

file_put_contents('c:/xampp/htdocs/sajib/sajib/includes/functions.php', $functionCode, FILE_APPEND);
echo "Function appended successfully.";

?>
