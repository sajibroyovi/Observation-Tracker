<?php
require_once __DIR__ . '/../config/app.php';
initSession();
$conn = getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for login attempt", ['username' => $_POST['username'] ?? 'unknown']);
        $error = "Security Validation Failed. Please refresh and try again.";
    } else {
        $username = cleanInput($_POST['username']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user['password'])) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['allowed_modules'] = !empty($user['allowed_modules']) ? explode(',', $user['allowed_modules']) : [];

                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
            mysqli_stmt_close($stmt);
        } else {
            log_error("Failed to prepare login statement", ['error' => mysqli_error($conn)]);
            $error = "Internal Server Error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Observation Tracker Portal</title>
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/uploads/bkash_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <script src="<?php echo ASSETS_URL; ?>/js/script.js" defer></script>
    <style>
        :root {
            --login-bg: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        body {
            background: var(--login-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            border-top: 4px solid var(--bkash-pink);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-logo {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--bkash-pink) 0%, var(--bkash-dark-pink) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: var(--bkash-shadow);
            padding: 12px;
        }

        .brand-logo svg {
            width: 100%;
            height: 100%;
            fill: white;
        }

        .login-card h2 {
            color: white;
            font-size: 26px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .login-card p.subtitle {
            color: #94a3b8;
            text-align: center;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            margin-left: 4px;
        }

        .input-group-modern {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-modern i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            transition: color 0.3s;
        }

        .input-group-modern .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 14px 16px 14px 48px;
            color: white;
            font-size: 15px;
            transition: all 0.3s;
        }

        .input-group-modern .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--bkash-pink);
            box-shadow: 0 0 0 4px rgba(209, 32, 83, 0.15);
            outline: none;
        }

        .input-group-modern .form-control:focus + i {
            color: var(--bkash-pink);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--bkash-pink) 0%, var(--bkash-dark-pink) 100%);
            border: none;
            border-radius: 14px;
            padding: 14px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            width: 100%;
            margin-top: 12px;
            transition: all 0.3s;
            box-shadow: var(--bkash-shadow);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(209, 32, 83, 0.4);
            filter: brightness(1.1);
        }

        .error-toast {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .demo-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 16px;
            margin-top: 32px;
        }

        .demo-box p {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            font-weight: 700;
        }

        .demo-credit {
            display: flex;
            justify-content: space-between;
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .demo-credit span:last-child {
            color: var(--bkash-pink);
            font-weight: 600;
        }

        /* Abstract Background Elements */
        .bg-blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(209, 32, 83, 0.1) 0%, rgba(209, 32, 83, 0) 70%);
            border-radius: 50%;
            z-index: 1;
        }

        .blob-1 { top: -100px; right: -100px; }
        .blob-2 { bottom: -100px; left: -100px; }
    </style>
</head>

<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo" style="background: white; border: 2px solid var(--bkash-pink); overflow: hidden; padding: 0;">
                <img src="<?php echo ASSETS_URL; ?>/uploads/bkash_logo.png" alt="bKash Logo" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <h2>Observation Tracker</h2>
            <p class="subtitle">Detailed L1 and L2 operational observations and insights</p>

            <?php if ($error): ?>
                <div class="error-toast">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo getCsrfField(); ?>
                <div class="mb-1">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group-modern">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autofocus>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group-modern">
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-bkash btn-login">
                    Sign In to Portal
                </button>
            </form>

        </div>
        
        <div class="text-center mt-4">
            <p style="color: #64748b; font-size: 13px;">&copy; <?php echo date('Y'); ?> Observation Tracker. All rights reserved.</p>
        </div>
    </div>
</body>

</html>