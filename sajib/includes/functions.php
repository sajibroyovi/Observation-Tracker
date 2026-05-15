<?php
/**
 * ============================================================================
 * SHIFT HANDOVER APPLICATION - FUNCTION LIBRARY
 * ============================================================================
 * 
 * Centralized library containing all reusable PHP functions for the
 * Shift Handover Application.
 * 
 * Sections:
 * 1. Database Functions
 * 2. Authentication & Session Functions
 * 3. Permission & Role Functions
 * 4. Utility Functions
 * 
 * @author Shift Handover Development Team
 * @version 1.0
 * @date 2026-02-08
 */

// ============================================================================
// 1. DATABASE FUNCTIONS
// ============================================================================

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getConnection() {
    static $conn = null;
    if ($conn === null) {
        $config = require __DIR__ . '/../config/database.php';
        
        $servername = $config['servername'];
        $username   = $config['username'];
        $password   = $config['password'];
        $dbname     = $config['dbname'];
        
        // Initialize mysqli and set a short timeout to prevent hanging
        $conn = mysqli_init();
        mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5); // 5 seconds timeout
        
        // Suppress warnings and attempt connection
        if (!@mysqli_real_connect($conn, $servername, $username, $password, $dbname)) {
            log_error("Critical Security Failure: Database connection failed.", ["context" => "Internal", "error" => mysqli_connect_error()]);
            die("<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
                    <h2 style='color: #d12053;'>Service Temporarily Unavailable</h2>
                    <p>Could not connect to the database. Please ensure <strong>MySQL service is running</strong> in your XAMPP control panel.</p>
                 </div>");
        }
    }
    return $conn;
}

/**
 * Close database connection
 * 
 * @param mysqli $conn Database connection to close
 * @return void
 */
function closeConnection($conn) {
    if ($conn) {
        mysqli_close($conn);
    }
}

/**
 * Basic data cleaning (trim, stripslashes)
 * Note: Use prepared statements for SQL and e() for HTML output instead of this
 * 
 * @param string $data Data to clean
 * @return string Cleaned data
 */
function cleanInput($data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Sanitize user input - DEPRECATED: Use prepared statements instead
 * 
 * @param mysqli $conn Database connection
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($conn, $data) {
    if ($data === null) return '';
    $data = cleanInput($data);
    return mysqli_real_escape_string($conn, $data);
}

/**
 * XSS Mitigation: Escape text for safe HTML output
 * 
 * @param string $text Text to escape
 * @return string Escaped text
 */
function e($text) {
    if ($text === null) return '';
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Log error to a file
 * 
 * @param string $message Error message
 * @param array $context Additional context for the log
 * @return void
 */
function log_error($message, $context = []) {
    $log_file = ROOT_PATH . '/logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'System';
    $context_json = !empty($context) ? json_encode($context) : '';
    
    $log_message = "[$timestamp] [User: $user_id] $message $context_json" . PHP_EOL;
    error_log($log_message, 3, $log_file);
}

/**
 * Execute a safe query (For internal/static use only)
 * WARNING: Do NOT use this with dynamic parameters. Use executePreparedStatement instead.
 * 
 * @param mysqli $conn Database connection
 * @param string $query SQL query to execute
 * @return mysqli_result|bool Query result or false on failure
 */
function executeQuery($conn, $query) {
    if (!$conn) {
        log_error("Database connection missing for query.");
        return false;
    }
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        log_error("Database Query Error", ["query_summary" => substr($query, 0, 50)]);
        return false;
    }
    return $result;
}

/**
 * Execute a prepared statement safely
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders (?)
 * @param string $types String representing parameter types (e.g., "ssi")
 * @param array $params Array of parameters to bind
 * @return mysqli_stmt|bool Prepared statement object or false on failure
 */
function executePreparedStatement($conn, $sql, $types = "", $params = []) {
    if (!$conn) {
        log_error("Database connection missing for prepared statement.");
        return false;
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        log_error("Failed to prepare statement", ["sql" => substr($sql, 0, 50), "error" => mysqli_error($conn)]);
        return false;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        log_error("Failed to execute prepared statement", ["sql" => substr($sql, 0, 50), "error" => mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        return false;
    }

    return $stmt;
}

// ============================================================================
// 2. AUTHENTICATION & SESSION FUNCTIONS
// ============================================================================

/**
 * Initialize session with timezone
 * 
 * @return void
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        date_default_timezone_set('Asia/Dhaka');
        session_start();
    }
}

/**
 * Check if user is authenticated, redirect to login if not
 * 
 * @param string $redirect_url URL to redirect to if not authenticated
 * @return void
 */
function requireAuth($redirect_url = null) {
    initSession();
    
    // Default to absolute login path if no URL provided
    if ($redirect_url === null) {
        $redirect_url = BASE_URL . '/login';
    }
    
    if (!isset($_SESSION['user_id'])) {
        redirectTo($redirect_url);
    }
}

/**
 * Get current user information
 * 
 * @return array User information (id, username, role)
 */
function getUserInfo() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Logout user and redirect
 * 
 * @param string $redirect_url URL to redirect after logout
 * @return void
 */
function logout($redirect_url = null) {
    initSession();
    session_destroy();
    
    // Default to absolute login path if no URL provided
    if ($redirect_url === null) {
        $redirect_url = BASE_URL . '/login';
    }
    
    redirectTo($redirect_url);
}

/**
 * Generate and store CSRF token if not exists
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    initSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token
 * 
 * @return string CSRF token
 */
function getCsrfToken() {
    return generateCsrfToken();
}

/**
 * Get HTML hidden input field for CSRF
 * 
 * @return string HTML input for CSRF
 */
function getCsrfField() {
    $token = getCsrfToken();
    $return_url = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">' . "\n" .
           '<input type="hidden" name="return_url" value="' . $return_url . '">';
}

/**
 * Validate CSRF token from request
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token) {
    initSession();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================================
// 3. PERMISSION & ROLE FUNCTIONS
// ============================================================================

/**
 * Check if user has a specific permission/role
 * 
 * @param string $required_role Required role to check
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($required_role) {
    if ($_SESSION['role'] === 'super_admin') {
        return true;
    }
    return $_SESSION['role'] === $required_role;
}

/**
 * Check if user can view a specific module
 * 
 * @param string $module_title Module title to check access for
 * @return bool True if user can view module, false otherwise
 */
function canViewModule($module_title) {
    if ($_SESSION['role'] === 'super_admin') {
        return true;
    }
    
    $allowed = $_SESSION['allowed_modules'] ?? [];
    if (!is_array($allowed)) {
        $allowed = explode(',', $allowed);
    }
    
    return in_array($module_title, $allowed);
}

/**
 * Check if user is a super admin
 * 
 * @return bool True if super admin, false otherwise
 */
function isSuperAdmin() {
    return $_SESSION['role'] === 'super_admin';
}

/**
 * Check if user is an admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return $_SESSION['role'] === 'admin';
}

/**
 * Check if user can edit L1 observations
 * Super Admin and L1 users can edit L1
 * 
 * @return bool True if can edit L1, false otherwise
 */
function canEditL1() {
    return in_array($_SESSION['role'], ['super_admin', 'l1']);
}

/**
 * Check if user can edit L2 observations
 * Super Admin, Admin, and L2 users can edit L2
 * 
 * @return bool True if can edit L2, false otherwise
 */
function canEditL2() {
    return in_array($_SESSION['role'], ['super_admin', 'admin', 'l2']);
}

/**
 * Check if user can add observations
 * Super Admin and L1 users can add observations
 * 
 * @return bool True if can add observation, false otherwise
 */
function canAddObservation() {
    return in_array($_SESSION['role'], ['super_admin', 'l1']);
}

/**
 * Check if user can edit global modules
 * Everyone except L2 can edit global modules
 * L2 can only edit observations
 * 
 * @return bool True if can edit global, false otherwise
 */
function canEditGlobal() {
    return $_SESSION['role'] !== 'l2';
}

/**
 * Check if user can dispatch shift handover
 * Super Admin and Admin can dispatch handover
 * 
 * @return bool True if can dispatch, false otherwise
 */
function canDispatchHandover() {
    return in_array($_SESSION['role'], ['super_admin', 'admin']);
}

// ============================================================================
// 4. UTILITY FUNCTIONS
// ============================================================================

/**
 * Get current shift based on time
 * Morning: 6 AM to 2 PM (06:00 - 13:59)
 * Evening: 2 PM to 10 PM (14:00 - 21:59)
 * Night: 10 PM to 6 AM (22:00 - 05:59)
 * 
 * @return string Current shift (Morning, Evening, or Night)
 */
function getCurrentShift() {
    $hour = (int)date('H');
    
    // Morning: 6 AM to 2 PM (06:00 - 13:59)
    if ($hour >= 6 && $hour < 14) {
        return 'Morning';
    }
    
    // Evening: 2 PM to 10 PM (14:00 - 21:59)
    if ($hour >= 14 && $hour < 22) {
        return 'Evening';
    }
    
    // Night: 10 PM to 6 AM (22:00 - 05:59)
    return 'Night';
}

/**
 * Format date with specified format
 * 
 * @param string $date Date to format
 * @param string $format Format string (default: 'Y-m-d H:i:s')
 * @return string Formatted date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date; // Return original if parsing fails
    }
    
    return date($format, $timestamp);
}

/**
 * Get current timestamp in standard format
 * 
 * @return string Current timestamp
 */
function getTimestamp() {
    return date('Y-m-d H:i:s');
}

/**
 * Check if a URL is safe for redirection (relative path or same domain)
 * 
 * @param string $url URL to check
 * @return bool True if safe, false otherwise
 */
function isSafeUrl($url) {
    if (empty($url)) return false;
    
    // Remove control characters
    $url = str_replace(["\r", "\n"], '', $url);
    
    // Case 1: Relative path starting with / (but not //)
    if (strpos($url, '/') === 0) {
        return strpos($url, '//') !== 0;
    }
    
    // Case 2: Relative path not starting with / (e.g. 'view.php')
    // If it doesn't contain :// or start with //, it's relative
    if (!preg_match('~^(https?:)?//~i', $url)) {
        return true;
    }
    
    // Case 3: Absolute URL - Must match our host
    $parsed = parse_url($url);
    if (!isset($parsed['host'])) return true; // Still relative if no host
    
    $base_parsed = parse_url(BASE_URL);
    $base_host = $base_parsed['host'] ?? ($_SERVER['HTTP_HOST'] ?? '');
    
    return $parsed['host'] === $base_host;
}

/**
 * Get a safe redirect URL from untrusted input
 * 
 * @param string|null $input The untrusted input URL (e.g. from $_GET['return_url'])
 * @param string $default The default URL to use if input is unsafe or empty
 * @return string A safe URL
 */
function getSafeRedirectUrl($input, $default) {
    if (empty($input)) return $default;
    
    // Parse the input URL
    $parsed = parse_url($input);
    if (!$parsed) return $default;
    
    // Validate host if present
    if (isset($parsed['host'])) {
        $base_parsed = parse_url(BASE_URL);
        $base_host = $base_parsed['host'] ?? '';
        
        // If host doesn't match base host, it's an external (and thus unsafe) redirect
        if ($parsed['host'] !== $base_host) {
            return $default;
        }
    }
    
    // Reconstruct the URL from trusted components to strip any hidden tricks (like CRLF or malformed paths)
    $safe_path = isset($parsed['path']) ? $parsed['path'] : '';
    $safe_query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $safe_fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
    
    if (isset($parsed['host'])) {
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '//';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        return $scheme . $parsed['host'] . $port . $safe_path . $safe_query . $safe_fragment;
    }
    
    // If it's a relative path, ensure it doesn't start with // (which would be protocol-relative to another domain)
    if (strpos($safe_path, '//') === 0) {
        return $default;
    }
    
    return $safe_path . $safe_query . $safe_fragment;
}

/**
 * Redirect to a URL safely
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirectTo($url) {
    // 1. Sanitize control characters (CRLF injection)
    $url = (string)str_replace(["\r", "\n"], '', $url);
    
    // 2. Default fallback
    $safe_url = BASE_URL . '/';
    
    // 3. Parse the URL
    $parsed = parse_url($url);
    if ($parsed) {
        // We IGNORE the scheme and host from the input URL.
        // We ONLY take the path, query, and fragment.
        // This ensures the redirect is ALWAYS on our own site.
        
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        
        if ($path !== '') {
            // Check if the path already starts with BASE_URL
            // We use a relative check to satisfy Snyk's constant-based analysis
            if (strpos($path, BASE_URL) === 0) {
                $safe_url = '/' . ltrim($path, '/');
            } else {
                $safe_url = BASE_URL . '/' . ltrim($path, '/');
            }
            $safe_url .= $query . $fragment;
        }
    }
    
    // 4. Force relative: ensure the result starts with / and NOT //
    $safe_url = '/' . ltrim(preg_replace('~^//+~', '/', $safe_url), '/');
    
    // Final check for Snyk: no data: or javascript:
    if (preg_match('~^(data|javascript):~i', $safe_url)) {
        $safe_url = BASE_URL . '/';
    }
    
    session_write_close();
    header('Location: ' . $safe_url);
    exit();
}

/**
 * Display success message
 * 
 * @param string $message Success message to display
 * @return void
 */
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Display error message
 * 
 * @param string $message Error message to display
 * @return void
 */
function showError($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 * 
 * @return string|null Success message or null
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear error message
 * 
 * @return string|null Error message or null
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Validate file upload
 * 
 * @param array $file File from $_FILES
 * @param array $allowed_types Allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array ['success' => bool, 'message' => string]
 */
function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $max_mb = $max_size / 1048576;
        return ['success' => false, 'message' => "File size exceeds {$max_mb}MB limit"];
    }
    
    // Check file type if specified
    if (!empty($allowed_types)) {
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
    }
    
    return ['success' => true, 'message' => 'File validation passed'];
}

/**
 * Generate a unique filename
 * 
 * @param string $original_name Original filename
 * @return string Unique filename
 */
function generateUniqueFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $filename = pathinfo($original_name, PATHINFO_FILENAME);
    $unique = uniqid() . '_' . time();
    return $filename . '_' . $unique . '.' . $extension;
}

/**
 * Safely move a record to the recycle bin before deletion
 * 
 * @param mysqli $conn Database connection
 * @param string $table_name Source table name
 * @param string $id_column Primary key column name
 * @param int|string $id_value Primary key value
 * @param string $module_name Human readable module name
 * @return bool True if successful, false otherwise
 */
function moveToRecycleBin($conn, $table_name, $id_column, $id_value, $module_name) {
    if (!$conn) return false;

    // 1. Fetch the existing record
    $fetch_sql = "SELECT * FROM `$table_name` WHERE `$id_column` = ?";
    $stmt = mysqli_prepare($conn, $fetch_sql);
    
    if (!$stmt) {
        log_error("Recycle Bin Error: Failed to prepare fetch statement", ["table" => $table_name, "error" => mysqli_error($conn)]);
        return false;
    }
    
    $type = is_numeric($id_value) ? "i" : "s";
    mysqli_stmt_bind_param($stmt, $type, $id_value);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        log_error("Recycle Bin Error: Record not found to backup", ["table" => $table_name, "id" => $id_value]);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $row = mysqli_fetch_assoc($result);
    $data_payload = json_encode($row);
    mysqli_stmt_close($stmt);
    
    // 2. Insert into recycle_bin
    $deleted_by = $_SESSION['username'] ?? 'System';
    $insert_sql = "INSERT INTO recycle_bin (module_name, table_name, original_id, data_payload, deleted_by) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$insert_stmt) {
        log_error("Recycle Bin Error: Failed to prepare insert statement", ["error" => mysqli_error($conn)]);
        return false;
    }
    
    mysqli_stmt_bind_param($insert_stmt, "ssiss", $module_name, $table_name, $id_value, $data_payload, $deleted_by);
    $success = mysqli_stmt_execute($insert_stmt);
    
    if (!$success) {
        log_error("Recycle Bin Error: Failed to insert backup", ["error" => mysqli_stmt_error($insert_stmt)]);
    }
    
    mysqli_stmt_close($insert_stmt);
    return $success;
}

/**
 * Send an email notification to an assigned technician
 * 
 * @param mysqli $conn Database connection
 * @param string $technician_username The username of the assigned technician
 * @param string $observation_name The title of the observation
 * @param string $team_name The assigned team(s)
 * @return bool True if successful, false otherwise
 */
function sendAssignmentEmail($conn, $technician_username, $observation_name, $team_name = "") {
    // Temporarily disable email notifications as requested
    return true;
    
    if (!$conn || empty($technician_username)) return false;

    // 1. Fetch technician email
    $stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE username = ?");
    if (!$stmt) return false;
    
    mysqli_stmt_bind_param($stmt, "s", $technician_username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $email = '';
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'];
        if (empty($email)) return false; // No email to send to
    } else {
        return false;
    }
    mysqli_stmt_close($stmt);

    // 2. Prepare PHPMailer securely
    require_once ROOT_PATH . '/vendor/PHPMailer-6.8.0/src/Exception.php';
    require_once ROOT_PATH . '/vendor/PHPMailer-6.8.0/src/PHPMailer.php';
    require_once ROOT_PATH . '/vendor/PHPMailer-6.8.0/src/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        // Sending from the original system sender
        $mail->Username = 'impsajibroy@gmail.com'; 
        $mail->Password = 'mbxs mvsy fkkg hagm'; 
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('impsajibroy@gmail.com', 'Observation Tracker');
        $mail->addAddress($email);

        // Add Team Group Mail as CC
        if (!empty($team_name)) {
            // Edit these to match your exact Outlook Team Group emails
            $team_mail_map = [
                'Tech Service Operations' => 'tech.so@bkash.com',
                'Tech Service Delivery' => 'tsd.so@bkash.com',
                'Central Monitoring Center' => 'soc.tech@bkash.com',
                'Network Operations' => 'network@bkash.com', // Added 's'
                'Data Center Operations' => 'dcoperations@bkash.com', // Placeholder if needed
                'Server Storage & Backup Management' => 'ssb@bkash.com',
                'Incident & Performance Management' => '', // Placeholder if needed
                'Database Management' => 'dba@bkash.com'
            ];
            
            // Handle multiple teams if separated by commas
            $assigned_teams = array_map('trim', explode(',', $team_name));
            foreach ($assigned_teams as $t) {
                if (isset($team_mail_map[$t])) {
                    $mail->addCC($team_mail_map[$t], $t . ' Team');
                } else {
                    log_error("Unmapped Team Name for CC", ['team' => $t]);
                }
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Observation Assigned: " . htmlspecialchars($observation_name);
        
        $message = "<html><head><style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
            h2 { color: #D12053; }
            .button { display: inline-block; padding: 10px 20px; background-color: #D12053; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        </style></head><body>";
        $message .= "<div class='container'>";
        $message .= "<h2>Observation Assigned</h2>";
        $message .= "<p>Hello <strong>" . htmlspecialchars($technician_username) . "</strong>,</p>";
        $message .= "<p>An observation has been assigned to you by " . htmlspecialchars($_SESSION['username'] ?? 'the system') . ".</p>";
        $message .= "<ul>";
        $message .= "<li><strong>Observation:</strong> " . htmlspecialchars($observation_name) . "</li>";
        $message .= "<li><strong>Assigned At:</strong> " . date('Y-m-d H:i') . "</li>";
        $message .= "</ul>";
        $message .= "<p>Please log in to the Observation Tracker to review the details and provide an update.</p>";
        $message .= "<a href='" . BASE_URL . "/modules/observations/view' class='button'>View Observations</a>";
        $message .= "<p style='margin-top: 30px; font-size: 11px; color: #777;'>This is an automated notification. Please do not reply directly to this email.</p>";
        $message .= "</div></body></html>";

        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (\Exception $e) {
        log_error("Failed to send assignment email", ['email' => $email, 'error' => $mail->ErrorInfo]);
        return false;
    }
}

/**
 * Send a WhatsApp notification using Local Node.js Bridge
 * 
 * @param string $phone The phone number (e.g., 88017...)
 * @param string $message The message text
 * @return bool True if successful, false otherwise
 */
function sendWhatsAppNotification($phone, $message) {
    if (empty($phone) || empty($message)) {
        return false;
    }

    $url = "http://localhost:3000/send";
    $data = [
        'phone' => $phone,
        'message' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        log_error("Local WhatsApp Bridge Error", ['code' => $http_code, 'response' => $response]);
        return false;
    }

    return true;
}

/**
 * Fetch technician details and send WhatsApp notification
 * 
 * @param mysqli $conn Database connection
 * @param string $technician_username The username of the assigned technician
 * @param string $observation_name The title of the observation
 * @return bool True if successful, false otherwise
 */
function sendAssignmentWhatsApp($conn, $technician_username, $observation_name) {
    if (!$conn || empty($technician_username)) return false;

    // 1. Fetch technician WhatsApp details
    $stmt = mysqli_prepare($conn, "SELECT phone_number FROM users WHERE username = ?");
    if (!$stmt) return false;
    
    mysqli_stmt_bind_param($stmt, "s", $technician_username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $phone = '';
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $phone = $row['phone_number'];
    }
    mysqli_stmt_close($stmt);

    if (empty($phone)) {
        return false; // Not configured for WhatsApp
    }

    // 2. Prepare message
    $message = "🔔 *New Observation Assigned*\n\n";
    $message .= "*Observation:* " . $observation_name . "\n";
    $message .= "*Assigned At:* " . date('Y-m-d H:i') . "\n\n";
    $message .= "Please log in to the Observation Tracker to review details.";

    // 3. Send via Local Bridge
    return sendWhatsAppNotification($phone, $message);
}