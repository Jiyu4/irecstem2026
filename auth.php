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
                    sendEmail($email, $name, 'IRECSTEM 2026 - Verification Code', '<p>Your code: ' . $verification_code . '</p>');
                    $message = 'A verification code has been sent to your email.';
                } catch (Exception $e) {
                    $message = 'Verification code: ' . $verification_code . ' (SMTP not configured)';
                }
                $message_type = 'success';
                $show_verify_form = true;
                $pending_email = $email;
                $pending_name = $name;
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
                    sendEmail($email, $user['name'] ?? 'User', 'IRECSTEM 2026 - Login Code', '<p>Your login code: ' . $login_code . '</p>');
                    $message = 'A login code has been sent to your email.';
                } catch (Exception $e) {
                    $message = 'Login code: ' . $login_code . ' (SMTP not configured)';
                }
                $message_type = 'success';
                $show_login_verify = true;
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
    header('Location: dashboard.php');
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
