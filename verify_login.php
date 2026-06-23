<?php
/**
 * IRECSTEM 2026 - Login Code Verification Page
 */

// Calculate base URL for assets
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = $protocol . $host . ($scriptPath !== '/' ? $scriptPath : '') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login | IRECSTEM 2026</title>
    <base href="<?php echo $baseUrl; ?>">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 60px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-dark) 100%);
        }
        .auth-card {
            background: white;
            padding: 50px;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 450px;
            box-shadow: var(--shadow-xl);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-header .logo {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        .auth-header h1 {
            color: var(--navy);
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        .auth-header p {
            color: var(--gray-500);
        }
        .auth-form .form-group {
            margin-bottom: 20px;
        }
        .auth-form label {
            display: block;
            color: var(--dark);
            font-weight: 500;
            margin-bottom: 8px;
        }
        .auth-form input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }
        .auth-form input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .auth-form .btn {
            width: 100%;
            margin-top: 10px;
        }
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--gray-200);
        }
        .auth-footer a {
            color: var(--primary);
            font-weight: 600;
        }
        .alert {
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .verification-code-input {
            text-align: center;
            font-size: 1.5rem !important;
            letter-spacing: 8px;
        }
        .resend-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--gray-500);
            font-size: 0.9rem;
            background: none;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .resend-link:hover {
            color: var(--primary);
        }
        @media (max-width: 480px) {
            .auth-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo"><i class="fas fa-globe-americas"></i></div>
                <h1>Enter Login Code</h1>
                <p>Check your email for the code</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="auth.php">
                <input type="hidden" name="action" value="verify_login">
                <p style="text-align: center; color: var(--gray-600); margin-bottom: 20px;">
                    Enter the 6-digit code sent to<br>
                    <strong><?php echo htmlspecialchars($_SESSION['login_email'] ?? ''); ?></strong>
                </p>
                <div class="form-group">
                    <label for="code">Login Code</label>
                    <input type="text" id="code" name="code" class="verification-code-input" maxlength="6" required placeholder="------" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <button type="submit" formaction="auth.php" name="action" value="resend_login" class="resend-link">
                    Resend code
                </button>
            </form>

            <div class="auth-footer">
                <a href="auth.php">Use different email</a>
            </div>
        </div>
    </div>
</body>
</html>
