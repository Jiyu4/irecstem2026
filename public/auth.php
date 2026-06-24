<?php
/**
 * IRECSTEM 2026 - Authentication Handler
 */

require_once 'config.php';

$message = '';
$message_type = '';
$show_verify_form = false;
$show_login_verify = false;
$pending_email = '';
$pending_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');

        if (empty($name) || empty($email)) {
            $message = 'Please fill in all fields.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
        } else {
            $db = users();
            if ($db->exists('email', $email)) {
                $message = 'This email has already registered. Please login below.';
                $message_type = 'error';
            } else {
                $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $verification_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $_SESSION['pending_registration'] = [
                    'name' => $name,
                    'email' => $email,
                    'code' => $verification_code,
                    'expiry' => $verification_expiry
                ];

                try {
                    $email_body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f4f4f4; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0038A8 0%, #00257a 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0 0 10px; color: #FCD116; font-size: 28px; font-weight: bold;">IRECSTEM 2026</h1>
                            <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 14px;">1st International Research and Extension Conference on STEM</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #0038A8; font-size: 22px; text-align: center;">Email Verification</h2>
                            <p style="margin: 0 0 30px; color: #555; font-size: 16px; line-height: 1.6; text-align: center;">Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                            <p style="margin: 0 0 30px; color: #555; font-size: 16px; line-height: 1.6; text-align: center;">Your verification code is:</p>
                            <!-- Code Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <div style="background: linear-gradient(135deg, #0038A8 0%, #004fc7 100%); border-radius: 12px; padding: 25px 40px; display: inline-block;">
                                            <span style="color: #FCD116; font-size: 36px; font-weight: bold; letter-spacing: 8px; font-family: monospace;">' . $verification_code . '</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <!-- Info -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 15px;">
                                        <p style="margin: 0 0 8px; color: #666; font-size: 13px;"><strong>Important:</strong></p>
                                        <ul style="margin: 0; padding-left: 20px; color: #666; font-size: 13px; line-height: 1.8;">
                                            <li>This code expires in <strong>15 minutes</strong></li>
                                            <li>Do not share this code with anyone</li>
                                            <li>If you did not request this, please ignore this email</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background: #0038A8; padding: 25px 30px; text-align: center;">
                            <p style="margin: 0 0 5px; color: #FCD116; font-size: 14px; font-weight: bold;">September 15-17, 2026</p>
                            <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 12px;">STATE UNIVERSITY OF NORTHERN NEGROS</p>
                        </td>
                    </tr>
                </table>
                <p style="color: #999; font-size: 12px; margin-top: 20px;">This is an automated message. Please do not reply.</p>
            </td>
        </tr>
    </table>
</body>
</html>';
                    sendEmail($email, $name, 'IRECSTEM 2026 - Verification Code', $email_body);
                    $message = 'A verification code has been sent to your email.';
                    $message_type = 'success';
                    $show_verify_form = true;
                    $pending_email = $email;
                    $pending_name = $name;
                } catch (Exception $e) {
                    $message = 'Unable to send email. Please check SMTP configuration.';
                    $message_type = 'error';
                    // Don't show the code - email failed, registration can't proceed
                    unset($_SESSION['pending_registration']);
                    $show_verify_form = false;
                }
            }
        }
    } elseif ($action === 'verify') {
        $code = sanitize($_POST['code'] ?? '');

        if (empty($_SESSION['pending_registration'])) {
            $message = 'Session expired. Please register again.';
            $message_type = 'error';
        } else {
            $pending = $_SESSION['pending_registration'];
            if (strtotime($pending['expiry']) < time()) {
                unset($_SESSION['pending_registration']);
                $message = 'Verification code expired. Please register again.';
                $message_type = 'error';
            } elseif ($code !== $pending['code']) {
                $message = 'Invalid verification code.';
                $message_type = 'error';
                $show_verify_form = true;
                $pending_email = $pending['email'];
                $pending_name = $pending['name'];
            } else {
                $db = users();
                $user = [
                    'name' => $pending['name'],
                    'email' => $pending['email'],
                    'status' => 'verified',
                    'verified_at' => date('Y-m-d H:i:s'),
                    'is_admin' => 0
                ];
                $user_id = $db->insert($user);
                if ($user_id) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $pending['email'];
                    $_SESSION['user_name'] = $pending['name'];
                    $_SESSION['is_admin'] = 0;
                    unset($_SESSION['pending_registration']);
                    header('Location: dashboard.php');
                    exit;
                }
            }
        }
    } elseif ($action === 'login') {
        $email = sanitize($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email.';
            $message_type = 'error';
        } else {
            $db = users();
            $user = $db->findBy('email', $email);
            if (!$user) {
                $message = 'No registration found. Please register first.';
                $message_type = 'error';
            } else {
                $login_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $db->update($user['id'], ['login_code' => $login_code]);
                $_SESSION['login_code'] = $login_code;
                $_SESSION['login_email'] = $email;
                $_SESSION['login_user_id'] = $user['id'];
                try {
                    $email_body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f4f4f4; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0038A8 0%, #00257a 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0 0 10px; color: #FCD116; font-size: 28px; font-weight: bold;">IRECSTEM 2026</h1>
                            <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 14px;">1st International Research and Extension Conference on STEM</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #0038A8; font-size: 22px; text-align: center;">Login Verification</h2>
                            <p style="margin: 0 0 30px; color: #555; font-size: 16px; line-height: 1.6; text-align: center;">Hello <strong>' . htmlspecialchars($user['name'] ?? 'User') . '</strong>,</p>
                            <p style="margin: 0 0 30px; color: #555; font-size: 16px; line-height: 1.6; text-align: center;">Your login code is:</p>
                            <!-- Code Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <div style="background: linear-gradient(135deg, #0038A8 0%, #004fc7 100%); border-radius: 12px; padding: 25px 40px; display: inline-block;">
                                            <span style="color: #FCD116; font-size: 36px; font-weight: bold; letter-spacing: 8px; font-family: monospace;">' . $login_code . '</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <!-- Info -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 15px;">
                                        <p style="margin: 0 0 8px; color: #666; font-size: 13px;"><strong>Important:</strong></p>
                                        <ul style="margin: 0; padding-left: 20px; color: #666; font-size: 13px; line-height: 1.8;">
                                            <li>This code expires in <strong>15 minutes</strong></li>
                                            <li>Do not share this code with anyone</li>
                                            <li>If you did not request this, please secure your account</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background: #0038A8; padding: 25px 30px; text-align: center;">
                            <p style="margin: 0 0 5px; color: #FCD116; font-size: 14px; font-weight: bold;">September 15-17, 2026</p>
                            <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 12px;">STATE UNIVERSITY OF NORTHERN NEGROS</p>
                        </td>
                    </tr>
                </table>
                <p style="color: #999; font-size: 12px; margin-top: 20px;">This is an automated message. Please do not reply.</p>
            </td>
        </tr>
    </table>
</body>
</html>';
                    sendEmail($email, $user['name'] ?? 'User', 'IRECSTEM 2026 - Login Code', $email_body);
                    $message = 'A login code has been sent to your email.';
                    $message_type = 'success';
                    $show_login_verify = true;
                } catch (Exception $e) {
                    $message = 'Unable to send email. Please check SMTP configuration.';
                    $message_type = 'error';
                    // Clear login session data since email failed
                    unset($_SESSION['login_code'], $_SESSION['login_email'], $_SESSION['login_user_id']);
                    $show_login_verify = false;
                }
            }
        }
    } elseif ($action === 'verify_login') {
        $code = sanitize($_POST['code'] ?? '');
        if (empty($_SESSION['login_code']) || $code !== $_SESSION['login_code']) {
            $message = 'Invalid login code.';
            $message_type = 'error';
        } else {
            $db = users();
            $user = $db->findById($_SESSION['login_user_id']);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'] ?? '';
                $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            }
            unset($_SESSION['login_code'], $_SESSION['login_email'], $_SESSION['login_user_id']);
            if ($user && ($user['is_admin'] ?? 0)) {
                header('Location: admin/');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }
    }
}

if (isLoggedIn()) {
    // User is already logged in - show option to logout or continue
    $current_user_email = $_SESSION['user_email'] ?? '';
    $current_user_name = $_SESSION['user_name'] ?? 'User';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Already Logged In | IRECSTEM 2026</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            body { background: linear-gradient(135deg, #0f1419 0%, #1a2332 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
            .card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(252,209,22,0.3); border-radius: 24px; padding: 40px; text-align: center; max-width: 450px; }
            .card h2 { color: #FCD116; margin-bottom: 10px; }
            .card p { color: rgba(255,255,255,0.7); margin-bottom: 30px; }
            .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 25px; font-weight: 600; text-decoration: none; margin: 5px; }
            .btn-primary { background: #0038A8; color: white; }
            .btn-danger { background: rgba(206,17,38,0.2); color: #ff6b6b; border: 1px solid rgba(206,17,38,0.3); }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>You're Already Logged In</h2>
            <p>You are currently logged in as:<br><strong style="color: #FCD116;"><?php echo htmlspecialchars($current_user_name); ?></strong><br><small><?php echo htmlspecialchars($current_user_email); ?></small></p>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-home"></i> Go to Dashboard</a>
            <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout & Register New</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['register']) ? 'Register' : 'Login'; ?> | IRECSTEM 2026</title>
    <base href="./">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="bg-slideshow">
        <div class="bg-slide bg-slide-1"></div>
        <div class="bg-slide bg-slide-2"></div>
        <div class="bg-slide bg-slide-3"></div>
    </div>
    <div class="bg-overlay"></div>
    <div class="bg-accents"></div>

    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.html">
                    <div class="nav-logo">
                        <img src="logo.png" alt="IRECSTEM Logo">
                    </div>
                    <div class="logo-text">
                        <span class="logo-main">IRECSTEM</span>
                        <span class="logo-year">2026</span>
                    </div>
                </a>
            </div>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle Navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="about.html" class="nav-link">About</a></li>
                <li><a href="call-for-papers.html" class="nav-link">Papers</a></li>
                <li><a href="speakers.html" class="nav-link">Speakers</a></li>
                <li><a href="program.html" class="nav-link">Program</a></li>
                <li><a href="venue.html" class="nav-link">Venue</a></li>
                <li><a href="contact.html" class="nav-link">Contact</a></li>
                <li class="nav-auth"><a href="auth.php" class="btn btn-primary active">Login</a></li>
            </ul>
        </div>
    </nav>

    <section class="hero" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="hero-container" style="padding-top: 100px; width: 100%;">
            <div class="auth-card" style="max-width: 450px; margin: 0 auto; background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(252,209,22,0.3); border-radius: 24px; padding: 40px; text-align: center;">

                <div style="margin-bottom: 30px;">
                    <span class="gov-badge" style="margin-bottom: 20px; display: inline-flex;">
                        <i class="fas fa-user-circle"></i>
                        <?php echo isset($_GET['register']) ? 'Create Account' : ($show_verify_form ? 'Enter Code' : 'Welcome'); ?>
                    </span>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>" style="padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 0.9rem; text-align: left;">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if ($show_verify_form): ?>
                <form method="POST" action="" style="text-align: left;">
                    <input type="hidden" name="action" value="verify">
                    <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px; font-size: 0.9rem;">
                        Enter the 6-digit code sent to:<br>
                        <strong style="color: var(--yellow);"><?php echo htmlspecialchars($pending_email); ?></strong>
                    </p>
                    <div class="form-group">
                        <input type="text" name="code" class="code-input" style="width: 100%; padding: 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1.5rem; text-align: center; letter-spacing: 8px;" maxlength="6" required placeholder="------" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1rem; margin-top: 10px;">Verify Code</button>
                    <p style="margin-top: 20px; color: rgba(255,255,255,0.5); font-size: 0.85rem;">
                        <a href="auth.php" style="color: var(--yellow);">Use different email</a>
                    </p>
                </form>

                <?php elseif ($show_login_verify): ?>
                <form method="POST" action="" style="text-align: left;">
                    <input type="hidden" name="action" value="verify_login">
                    <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px; font-size: 0.9rem;">
                        Enter the login code sent to:<br>
                        <strong style="color: var(--yellow);"><?php echo htmlspecialchars($_SESSION['login_email'] ?? ''); ?></strong>
                    </p>
                    <div class="form-group">
                        <input type="text" name="code" class="code-input" style="width: 100%; padding: 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1.5rem; text-align: center; letter-spacing: 8px;" maxlength="6" required placeholder="------" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1rem; margin-top: 10px;">Login</button>
                    <p style="margin-top: 20px; color: rgba(255,255,255,0.5); font-size: 0.85rem;">
                        <a href="auth.php" style="color: var(--yellow);">Use different email</a>
                    </p>
                </form>

                <?php elseif (isset($_GET['register'])): ?>
                <form method="POST" action="" style="text-align: left;">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px; font-weight: 500;">Full Name</label>
                        <input type="text" name="name" required placeholder="Enter your full name" style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px; font-weight: 500;">Email Address</label>
                        <input type="email" name="email" required placeholder="Enter your email" style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1rem; margin-top: 10px;">Create Account</button>
                </form>
                <p style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); font-size: 0.9rem;">
                    Already have an account? <a href="auth.php" style="color: var(--yellow); font-weight: 600;">Login here</a>
                </p>

                <?php else: ?>
                <form method="POST" action="" style="text-align: left;">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px; font-weight: 500;">Email Address</label>
                        <input type="email" name="email" required placeholder="Enter your registered email" style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1rem; margin-top: 10px;">Send Login Code</button>
                </form>
                <p style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); font-size: 0.9rem;">
                    Don't have an account? <a href="auth.php?register=1" style="color: var(--yellow); font-weight: 600;">Register here</a>
                </p>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <button class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="script.js"></script>
</body>
</html>
