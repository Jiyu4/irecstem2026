<?php
/**
 * IRECSTEM 2026 - Authentication Handler
 * Login, Register (JSON Database)
 */

require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $institution = sanitize($_POST['institution'] ?? '');
        $country = sanitize($_POST['country'] ?? '');
        $dietary = sanitize($_POST['dietary'] ?? '');
        $participation_type = sanitize($_POST['participation_type'] ?? 'in-person');

        if (empty($full_name) || empty($email) || empty($password)) {
            $message = 'Please fill in all required fields.';
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
                $user = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => $password_hash,
                    'institution' => $institution,
                    'country' => $country,
                    'dietary' => $dietary,
                    'participation_type' => $participation_type,
                    'status' => 'approved',
                    'is_admin' => 0
                ];
                $user_id = $db->insert($user);

                if ($user_id) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['is_admin'] = 0;
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $message = 'Registration failed. Please try again.';
                    $message_type = 'error';
                }
            }
        }
    } elseif ($action === 'login') {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $message = 'Please enter both email and password.';
            $message_type = 'error';
        } else {
            $db = users();
            $user = $db->findBy('email', $email);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
                if ($user['is_admin'] ?? 0) {
                    header('Location: admin/');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                $message = 'Invalid email or password.';
                $message_type = 'error';
            }
        }
    }
}

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['register']) ? 'Register' : 'Login'; ?> | IRECSTEM 2026</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Auth Page Styles */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 40px;
            position: relative;
            z-index: 10;
        }

        .auth-container {
            width: 100%;
            max-width: 950px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            background: rgba(10, 22, 40, 0.95);
            border: 2px solid rgba(252, 209, 22, 0.3);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        /* Left Side - Info */
        .auth-info {
            background: linear-gradient(180deg, rgba(0, 56, 168, 0.8), rgba(0, 37, 122, 0.9));
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .auth-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('logo.png') center/80px no-repeat;
            opacity: 0.1;
        }

        .auth-info-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            position: relative;
            z-index: 1;
        }

        .auth-info-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .auth-info h2 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--yellow);
            position: relative;
            z-index: 1;
        }

        .auth-info p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .auth-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            position: relative;
            z-index: 1;
        }

        .auth-stat {
            text-align: center;
        }

        .auth-stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--yellow);
            display: block;
            text-shadow: 0 0 20px rgba(252, 209, 22, 0.5);
        }

        .auth-stat-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Right Side - Form */
        .auth-form-container {
            padding: 50px 45px;
            position: relative;
            z-index: 1;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .auth-header h2 {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 8px;
        }

        .auth-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group label i {
            color: var(--yellow);
            margin-right: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--blue);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 20px rgba(0, 56, 168, 0.3);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23ffffff' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            cursor: pointer;
        }

        .form-select option {
            background: var(--dark);
            color: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-checkbox input {
            width: 18px;
            height: 18px;
            accent-color: var(--blue);
            cursor: pointer;
        }

        .form-checkbox label {
            margin-bottom: 0;
            cursor: pointer;
        }

        .form-checkbox a {
            color: var(--yellow);
        }

        .form-checkbox a:hover {
            text-decoration: underline;
        }

        .form-submit {
            width: 100%;
            margin-top: 15px;
        }

        /* Alert */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(0, 166, 81, 0.2);
            border: 1px solid rgba(0, 166, 81, 0.4);
            color: #86efac;
        }

        /* Footer Link */
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .auth-footer p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--yellow);
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 800px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 450px;
            }

            .auth-info {
                padding: 40px 30px;
            }

            .auth-form-container {
                padding: 40px 30px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .auth-stats {
                gap: 20px;
            }

            .auth-stat-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Slideshow -->
    <div class="bg-slideshow">
        <div class="bg-slide"></div>
        <div class="bg-slide"></div>
    </div>
    <div class="bg-overlay"></div>
    <div class="bg-accents"></div>
    <div class="bg-flag-accent"></div>

    <!-- Navigation -->
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
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="about.html" class="nav-link">About</a></li>
                <li><a href="call-for-papers.html" class="nav-link">Papers</a></li>
                <li><a href="speakers.html" class="nav-link">Speakers</a></li>
                <li><a href="program.html" class="nav-link">Program</a></li>
                <li><a href="venue.html" class="nav-link">Venue</a></li>
                <li><a href="contact.html" class="nav-link">Contact</a></li>
            </ul>
        </div>
    </nav>

    <main class="auth-page">
        <div class="auth-container">
            <!-- Left Side - Info -->
            <div class="auth-info">
                <div class="auth-info-logo">
                    <img src="logo.png" alt="IRECSTEM Logo">
                </div>
                <h2><?php echo isset($_GET['register']) ? 'Join IRECSTEM 2026' : 'Welcome Back'; ?></h2>
                <p><?php echo isset($_GET['register'])
                    ? 'Create your account to submit papers and register for the conference in Old Sagay, Philippines.'
                    : 'Access your account to manage your submissions and registration.'; ?></p>
                <div class="auth-stats">
                    <div class="auth-stat">
                        <span class="auth-stat-number">500+</span>
                        <span class="auth-stat-label">Attendees</span>
                    </div>
                    <div class="auth-stat">
                        <span class="auth-stat-number">50+</span>
                        <span class="auth-stat-label">Papers</span>
                    </div>
                    <div class="auth-stat">
                        <span class="auth-stat-number">20+</span>
                        <span class="auth-stat-label">Countries</span>
                    </div>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="auth-form-container">
                <div class="auth-header">
                    <h2><?php echo isset($_GET['register']) ? 'Create Account' : 'Login'; ?></h2>
                    <p>IRECSTEM 2026 - Old Sagay, Philippines</p>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['register'])): ?>
                <!-- Registration Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <label><i class="fas fa-user"></i>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="Enter your full name" class="form-input">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i>Email Address *</label>
                        <input type="email" name="email" required placeholder="Enter your email" class="form-input">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i>Password *</label>
                            <input type="password" name="password" required minlength="6" placeholder="Min 6 characters" class="form-input">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i>Confirm *</label>
                            <input type="password" name="confirm_password" required placeholder="Confirm password" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-users"></i>Participation Type</label>
                        <select name="participation_type" class="form-input form-select">
                            <option value="in-person">In-Person (Venue)</option>
                            <option value="virtual">Virtual (Online)</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i>Institution</label>
                            <input type="text" name="institution" placeholder="Your organization" class="form-input">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-globe"></i>Country</label>
                            <input type="text" name="country" placeholder="Your country" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-utensils"></i>Dietary Requirements</label>
                        <input type="text" name="dietary" placeholder="e.g., Vegetarian, None" class="form-input">
                    </div>

                    <div class="form-group form-checkbox">
                        <input type="checkbox" name="terms" required id="terms">
                        <label for="terms">I agree to the <a href="#">Terms</a> and <a href="#">Privacy Policy</a></label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large form-submit">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="auth.php">Login here</a></p>
                </div>

                <?php else: ?>
                <!-- Login Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i>Email Address</label>
                        <input type="email" name="email" required placeholder="Enter your email" class="form-input">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i>Password</label>
                        <input type="password" name="password" required placeholder="Enter your password" class="form-input">
                    </div>

                    <div class="form-group form-checkbox">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large form-submit">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="auth.php?register=1">Register here</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <button class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="script.js"></script>
</body>
</html>
