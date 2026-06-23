<?php
/**
 * IRECSTEM 2026 - Admin Account Setup
 * Run this once to create the first admin account
 */

require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $message = 'Please fill in all fields.';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        $db = users();

        if ($db->exists('email', $email)) {
            $message = 'An account with this email already exists.';
            $message_type = 'error';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $admin = [
                'name' => $name,
                'email' => $email,
                'password' => $password_hash,
                'status' => 'verified',
                'is_admin' => 1,
                'verified_at' => date('Y-m-d H:i:s')
            ];

            if ($db->insert($admin)) {
                $message = 'Admin account created! You can now login at auth.php';
                $message_type = 'success';
            } else {
                $message = 'Failed to create admin account.';
                $message_type = 'error';
            }
        }
    }
}

// Check if admin already exists
$db = users();
$admins = array_filter($db->all(), function($u) { return ($u['is_admin'] ?? false) == 1; });

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
    <title>Setup Admin | IRECSTEM 2026</title>
    <base href="<?php echo $baseUrl; ?>">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1B3A57 0%, #0d1f30 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        h1 {
            color: #1B3A57;
            text-align: center;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #1B3A57;
            font-weight: 600;
            margin-bottom: 8px;
        }
        input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #F28C28;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #F28C28, #d97720);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(242, 140, 40, 0.4);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .existing {
            text-align: center;
            padding: 20px;
            background: #f0fdf4;
            border-radius: 8px;
            color: #16a34a;
        }
        .logo {
            font-size: 3rem;
            color: #F28C28;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🌐</div>
        <h1>IRECSTEM 2026</h1>
        <p class="subtitle">Admin Account Setup</p>

        <?php if (count($admins) > 0): ?>
            <div class="existing">
                <p><strong>✓ Admin account already exists!</strong></p>
                <p style="margin-top: 10px; font-size: 0.9rem; color: #666;">
                    You can login at <a href="auth.php" style="color: #F28C28;">auth.php</a>
                </p>
            </div>
        <?php else: ?>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="warning">
                ⚠️ <strong>Security Notice:</strong> Delete this file (setup-admin.php) after creating your admin account.
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter your name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Min 6 characters" minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>

                <button type="submit">Create Admin Account</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
