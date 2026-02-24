require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-6.8.0/src/Exception.php';
require 'PHPMailer-6.8.0/src/PHPMailer.php';
require 'PHPMailer-6.8.0/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for send email attempt", ['shift' => $_POST['shift'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $shift = cleanInput($_POST['shift']);
    $date = cleanInput($_POST['date']);

    // Compose email content as HTML
    $subject = "Shift Handover Report - $shift Shift on $date";
    $message = "<html><head><style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #D12053; text-align: center; }
        h2 { color: #D12053; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #D12053; color: white; padding: 10px; text-align: center; }
        td { padding: 8px; text-align: center; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style></head><body>";
    $message .= "<h1>Shift Handover Report</h1>";
    $message .= "<p><strong>Shift:</strong> $shift</p>";
    $message .= "<p><strong>Date:</strong> $date</p>";

    // Query each table
    $tables = [
        'enable_disable' => ['date_field' => null, 'fields' => ['service_name', 'action_date', 'action_taken', 'action_taken_by', 'reference'], 'headers' => ['Service Name', 'Action Date', 'Action Taken', 'Action Taken By', 'Reference']],
        'pending_mail' => ['date_field' => null, 'fields' => ['subject_line', 'priority', 'status'], 'headers' => ['Subject Line', 'Priority', 'Status']],
        'security_mail' => ['date_field' => null, 'fields' => ['subject_line', 'priority', 'status'], 'headers' => ['Subject Line', 'Priority', 'Status']],
        'cr_list' => ['date_field' => null, 'fields' => ['cr_subject', 'impacted_area', 'cr_start_time', 'cr_end_time', 'downtime', 'cr_meeting_attended'], 'headers' => ['CR Subject', 'Impacted Area', 'CR Start Time', 'CR End Time', 'Downtime', 'CR Meeting Attended']],
        'promo_banner' => ['date_field' => null, 'fields' => ['subject_line', 'start_date', 'status'], 'headers' => ['Subject Line', 'Start Date', 'Status']],
        'service_outage' => ['date_field' => null, 'fields' => ['details', 'incident_id', 'problem_ticket', 'status', 'technician'], 'headers' => ['Details', 'Incident ID', 'Problem Ticket', 'Status', 'Technician']],
        'ssl_certificate' => ['date_field' => null, 'fields' => ['certificate_name', 'expiration_date', 'renewal_date', 'issues'], 'headers' => ['Certificate Name', 'Expiration Date', 'Renewal Date', 'Issues']],
        'campaign' => ['date_field' => null, 'fields' => ['campaign_name', 'start_date', 'status', 'description'], 'headers' => ['Campaign Name', 'Start Date', 'Status', 'Description']]
    ];

    foreach ($tables as $table => $info) { 
        $message .= "<h2>" . e(ucfirst(str_replace('_', ' ', $table))) . "</h2>";
        
        // Use prepared statements for all table queries
        $query = "SELECT * FROM " . preg_replace('/[^a-zA-Z0-9_]/', '', $table); // Basic hygiene
        if ($info['date_field']) {
            $query .= " WHERE DATE(" . $info['date_field'] . ") = ?";
            $stmt_table = executePreparedStatement($conn, $query, "s", [$date]);
        } else {
            $stmt_table = executePreparedStatement($conn, $query);
        }

        if ($stmt_table) {
            $result = mysqli_stmt_get_result($stmt_table);

        if ($result && mysqli_num_rows($result) > 0) {
            $message .= "<table><thead><tr>";
            foreach ($info['headers'] as $header) {
                $message .= "<th>" . e($header) . "</th>";
            }
            $message .= "</tr></thead><tbody>";
            while ($row = mysqli_fetch_assoc($result)) {
                $message .= "<tr>";
                foreach ($info['fields'] as $field) {
                    $value = isset($row[$field]) ? $row[$field] : 'N/A';
                    $message .= "<td>" . e($value) . "</td>";
                }
                $message .= "</tr>";
            }
            $message .= "</tbody></table>";
        } else {
            $message .= "<p>No records found for " . e($table) . "</p>";
        }
        if (isset($stmt_table)) {
            mysqli_stmt_close($stmt_table);
            unset($stmt_table);
        }
    }

    $message .= "</body></html>";

    // Insert into handover table using utility
    $stmt_handover = executePreparedStatement($conn, "INSERT INTO handover (shift, handover_date) VALUES (?, ?)", "ss", [$shift, $date]);
    if ($stmt_handover) {
        mysqli_stmt_close($stmt_handover);
    } else {
        log_error("Failed to log handover in database", ['shift' => $shift, 'date' => $date]);
    }

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'impsajibroy@gmail.com'; // Replace with your Gmail address
        $mail->Password = 'mbxs mvsy fkkg hagm'; // Replace with your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('impsajibroy@gmail.com', 'Shift Handover System'); // Replace with your Gmail
        $mail->addAddress('impsajibroy@gmail.com'); // Recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message); // Plain text version

        $mail->send();
        echo "<script>alert('Mail sent successfully'); window.location.href='../../';</script>";
    } catch (Exception $e) {
        log_error("PHPMailer Error", ['error' => $mail->ErrorInfo]);
        echo "<script>alert('Critical Error: Failed to dispatch shift report.'); window.location.href='../../';</script>";
    }
}

mysqli_close($conn);
?>
