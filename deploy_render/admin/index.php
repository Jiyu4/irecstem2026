<?php
/**
 * IRECSTEM 2026 - Admin Panel
 * Paper Submission and Conference Registration Management
 */

require_once '../config.php';
requireAdmin();

$message = '';
$message_type = '';

// Get data
$db_users = users();
$db_papers = papers();
$db_reg = registrations();

$all_users = $db_users->all();
$participants = array_filter($all_users, function($u) { return !($u['is_admin'] ?? false); });
$admins = array_filter($all_users, function($u) { return ($u['is_admin'] ?? false) == 1; });
$current_admin = $db_users->findById($_SESSION['user_id']);

// Handle actions
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);

    // Registration Management
    if ($_GET['action'] === 'approve_registration' && $id > 0) {
        $reg = $db_reg->findById($id);
        if ($reg) {
            $db_reg->update($id, ['status' => 'approved', 'updated_at' => date('Y-m-d H:i:s')]);

            // Send email notification
            $email_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #F28C28;'>
                    <h1 style='color: #1B3A57; font-size: 24px; margin: 0;'>IRECSTEM 2026</h1>
                    <p style='color: #666; margin: 10px 0 0;'>Registration Approved!</p>
                </div>
                <div style='padding: 20px; color: #333;'>
                    <p>Dear <strong>{$reg['user_name']}</strong>,</p>
                    <p>We are pleased to inform you that your conference registration has been <strong style='color: #16a34a;'>APPROVED</strong>!</p>
                    <div style='background: #dcfce7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;'>
                        <h3 style='color: #16a34a; margin: 0 0 15px;'>Registration Details:</h3>
                        <p><strong>Name:</strong> {$reg['user_name']}</p>
                        <p><strong>Category:</strong> " . ucfirst($reg['category']) . "</p>
                        <p><strong>Institution:</strong> {$reg['institution']}</p>
                    </div>
                    <p>Please keep this email as your confirmation. We look forward to seeing you at the conference!</p>
                </div>
                <div style='background: #1B3A57; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;'>
                    <p style='margin: 0;'><strong>September 15-17, 2026</strong></p>
                    <p style='margin: 5px 0 0;'>STATE UNIVERSITY OF NORTHER NEGROS</p>
                </div>
            </div>";

            try {
                sendEmail($reg['user_email'], $reg['user_name'], 'IRECSTEM 2026 - Registration Approved!', $email_body);
            } catch (Exception $e) {
                // Continue even if email fails
            }

            $message = 'Registration approved successfully! Email notification sent.';
            $message_type = 'success';
        }
    }
    elseif ($_GET['action'] === 'reject_registration' && $id > 0) {
        $reg = $db_reg->findById($id);
        if ($reg) {
            $db_reg->update($id, ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')]);

            // Send email notification
            $email_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #dc2626;'>
                    <h1 style='color: #1B3A57; font-size: 24px; margin: 0;'>IRECSTEM 2026</h1>
                    <p style='color: #666; margin: 10px 0 0;'>Registration Update</p>
                </div>
                <div style='padding: 20px; color: #333;'>
                    <p>Dear <strong>{$reg['user_name']}</strong>,</p>
                    <p>We regret to inform you that your conference registration has been <strong style='color: #dc2626;'>REJECTED</strong>.</p>
                    <p>If you believe this is an error or would like more information, please contact the conference organizers.</p>
                </div>
                <div style='background: #1B3A57; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;'>
                    <p style='margin: 0;'>Contact us: info@irecstem2026.org</p>
                </div>
            </div>";

            try {
                sendEmail($reg['user_email'], $reg['user_name'], 'IRECSTEM 2026 - Registration Update', $email_body);
            } catch (Exception $e) {
                // Continue even if email fails
            }

            $message = 'Registration rejected. Email notification sent.';
            $message_type = 'success';
        }
    }
    elseif ($_GET['action'] === 'cancel_registration' && $id > 0) {
        $db_reg->update($id, ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')]);
        $message = 'Registration cancelled.';
        $message_type = 'success';
    }
    elseif ($_GET['action'] === 'delete_registration' && $id > 0) {
        $db_reg->delete($id);
        $message = 'Registration deleted successfully!';
        $message_type = 'success';
    }

    // Paper Management
    elseif ($_GET['action'] === 'delete_paper' && $id > 0) {
        $paper = $db_papers->findById($id);
        if ($paper && !empty($paper['file_path'])) {
            $filename = basename($paper['file_path']);
            $file_path = '../uploads/' . $filename;
            if (is_file($file_path)) {
                unlink($file_path);
            }
        }
        $db_papers->delete($id);
        $message = 'Paper deleted successfully!';
        $message_type = 'success';
    }

    elseif ($_GET['action'] === 'export_registrations') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="irecstem-registrations-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Name', 'Email', 'Institution', 'Category', 'Country', 'Status', 'Registered At']);
        foreach ($db_reg->all() as $r) {
            fputcsv($output, [$r['id'], $r['user_name'], $r['user_email'], $r['institution'], $r['category'], $r['country'], $r['status'], $r['registered_at']]);
        }
        fclose($output);
        exit;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';

    // Paper Status Update
    if ($post_action === 'update_paper_status') {
        $paper_id = intval($_POST['paper_id'] ?? 0);
        $new_status = sanitize($_POST['new_status'] ?? '');
        $admin_notes = sanitize($_POST['admin_notes'] ?? '');

        if ($paper_id > 0 && in_array($new_status, ['pending', 'under_review', 'accepted', 'rejected'])) {
            $paper = $db_papers->findById($paper_id);
            if ($paper) {
                $db_papers->update($paper_id, [
                    'status' => $new_status,
                    'admin_notes' => $admin_notes,
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]);

                // Get user for email
                $paper_user = $db_users->findById($paper['user_id']);
                if ($paper_user) {
                    $status_text = [
                        'pending' => 'Submitted',
                        'under_review' => 'Under Review',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected'
                    ];

                    $email_body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                        <div style='text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #F28C28;'>
                            <h1 style='color: #1B3A57; font-size: 24px; margin: 0;'>IRECSTEM 2026</h1>
                            <p style='color: #666; margin: 10px 0 0;'>Paper Status Update</p>
                        </div>
                        <div style='padding: 20px; color: #333;'>
                            <p>Dear <strong>{$paper_user['name']}</strong>,</p>
                            <p>Your paper status has been updated:</p>
                            <div style='background: #f7f7f7; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                <p><strong>Title:</strong> {$paper['title']}</p>
                                <p><strong>New Status:</strong> <span style='color: " . ($new_status === 'accepted' ? '#16a34a' : ($new_status === 'rejected' ? '#dc2626' : '#F28C28')) . "; font-weight: bold;'>{$status_text[$new_status]}</span></p>
                            </div>
                            " . (!empty($admin_notes) ? "<p><strong>Notes:</strong> {$admin_notes}</p>" : "") . "
                        </div>
                        <div style='background: #1B3A57; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;'>
                            <p style='margin: 0;'>September 15-17, 2026 | STATE UNIVERSITY OF NORTHER NEGROS</p>
                        </div>
                    </div>";

                    try {
                        sendEmail($paper_user['email'], $paper_user['name'], 'IRECSTEM 2026 - Paper Status Update', $email_body);
                    } catch (Exception $e) {
                        // Continue even if email fails
                    }
                }

                $message = 'Paper status updated successfully!';
                $message_type = 'success';
            }
        }
    }

    // Settings
    elseif ($post_action === 'save_settings') {
        $fields = ['conference_name', 'conference_date', 'conference_location', 'registration_open', 'contact_email', 'contact_phone'];
        foreach ($fields as $field) {
            setSetting($field, sanitize($_POST[$field] ?? ''));
        }
        $message = 'Settings saved successfully!';
        $message_type = 'success';
    }

    // Create Admin
    elseif ($post_action === 'create_admin') {
        $name = sanitize($_POST['admin_name'] ?? '');
        $email = sanitize($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        if ($name && $email && $password && strlen($password) >= 6) {
            if (!$db_users->exists('email', $email)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $db_users->insert([
                    'name' => $name, 'email' => $email, 'password' => $password_hash,
                    'status' => 'verified', 'is_admin' => 1, 'verified_at' => date('Y-m-d H:i:s')
                ]);
                $message = 'Admin account created!';
                $message_type = 'success';
            } else {
                $message = 'Email already exists.';
                $message_type = 'error';
            }
        } else {
            $message = 'Please fill all fields correctly.';
            $message_type = 'error';
        }
    }

    // Send Email
    elseif ($post_action === 'send_email') {
        $subject = sanitize($_POST['email_subject'] ?? '');
        $message_content = sanitize($_POST['email_message'] ?? '');
        $send_to = $_POST['send_to'] ?? 'all';
        $selected_users = $_POST['selected_users'] ?? [];

        if ($subject && $message_content) {
            $email_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #F28C28;'>
                    <h1 style='color: #1B3A57; font-size: 24px; margin: 0;'>IRECSTEM 2026</h1>
                </div>
                <div style='padding: 20px; color: #333; line-height: 1.8;'>
                    " . nl2br($message_content) . "
                </div>
                <div style='background: #1B3A57; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;'>
                    <p style='margin: 0;'>1st International Research and Extension Conference on STEM</p>
                </div>
            </div>";

            $sent = 0;
            $failed = 0;
            $recipients = [];

            if ($send_to === 'selected' && count($selected_users) > 0) {
                $recipients = $selected_users;
            } elseif ($send_to === 'registrations') {
                foreach ($db_reg->findAll(['status' => 'approved']) as $r) {
                    $recipients[] = $r['user_email'];
                }
            } else {
                foreach ($participants as $p) {
                    $recipients[] = $p['email'];
                }
            }

            foreach ($recipients as $email) {
                try {
                    sendEmail($email, '', $subject, $email_body);
                    $sent++;
                } catch (Exception $e) {
                    $failed++;
                }
            }

            if ($sent > 0 && $failed == 0) {
                $message = "Email sent successfully to $sent recipient(s)!";
                $message_type = 'success';
            } elseif ($sent > 0 && $failed > 0) {
                $message = "Sent to $sent, $failed failed.";
                $message_type = 'warning';
            } else {
                $message = "Failed to send email.";
                $message_type = 'error';
            }
        } else {
            $message = 'Subject and message are required.';
            $message_type = 'error';
        }
    }
}

// Refresh data
$all_users = $db_users->all();
$participants = array_filter($all_users, function($u) { return !($u['is_admin'] ?? false); });
$admins = array_filter($all_users, function($u) { return ($u['is_admin'] ?? false) == 1; });
$papers_list = $db_papers->all();
$registrations_list = $db_reg->all();

// Get settings
$conference_name = getSetting('conference_name', 'IRECSTEM 2026');
$conference_date = getSetting('conference_date', 'September 15-17, 2026');
$conference_location = getSetting('conference_location', 'TBA');
$registration_open = getSetting('registration_open', '1');
$contact_email = getSetting('contact_email', '');
$contact_phone = getSetting('contact_phone', '');

// Stats
$total_registrations = count($db_reg->all());
$pending_registrations = count($db_reg->findAll(['status' => 'pending']));
$approved_registrations = count($db_reg->findAll(['status' => 'approved']));
$rejected_registrations = count($db_reg->findAll(['status' => 'rejected']));
$total_papers = count($papers_list);
$pending_papers = count($db_papers->findAll(['status' => 'pending']));
$accepted_papers = count($db_papers->findAll(['status' => 'accepted']));
$rejected_papers = count($db_papers->findAll(['status' => 'rejected']));

// Calculate base URL for assets
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = $protocol . $host . ($scriptPath !== '/' ? $scriptPath : '') . '/';
// Parent directory base URL for shared assets
$parentBaseUrl = $protocol . $host . ($scriptPath !== '/' ? dirname($scriptPath) : '') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | IRECSTEM 2026</title>
    <base href="<?php echo $baseUrl; ?>">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* ========================================
           Background Slideshow
        ======================================== */
        .bg-slideshow {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
        }

        .bg-slide {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            animation: slideSwitch 20s infinite;
        }

        .bg-slide:nth-child(1) {
            background-image: url('../bg-main.jpg');
            animation-delay: 0s;
        }

        .bg-slide:nth-child(2) {
            background-image: url('../bg-design.jpg');
            animation-delay: 10s;
        }

        @keyframes slideSwitch {
            0%, 45% { opacity: 1; }
            50%, 95% { opacity: 0; }
            100% { opacity: 1; }
        }

        .bg-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 1;
            background: linear-gradient(135deg, rgba(15, 20, 25, 0.75) 0%, rgba(10, 22, 40, 0.8) 50%, rgba(26, 35, 50, 0.75) 100%);
        }

        .bg-accents {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 2;
            background:
                radial-gradient(ellipse at 10% 15%, rgba(0, 56, 168, 0.25) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 85%, rgba(206, 17, 38, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 10%, rgba(252, 209, 22, 0.12) 0%, transparent 40%),
                radial-gradient(ellipse at 10% 90%, rgba(252, 209, 22, 0.08) 0%, transparent 35%);
            animation: accentPulse 15s ease-in-out infinite;
        }

        @keyframes accentPulse {
            0%, 100% { opacity: 0.8; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
        }

        .bg-flag-accent {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 3;
            background:
                linear-gradient(180deg, rgba(0, 56, 168, 0.08) 0%, transparent 30%),
                linear-gradient(0deg, rgba(206, 17, 38, 0.06) 0%, transparent 25%);
        }

        /* Navbar with glass effect */
        .navbar.scrolled {
            background: rgba(15, 20, 25, 0.95) !important;
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.4);
        }

        .nav-logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 8px;
        }

        .admin-wrapper {
            position: relative;
            z-index: 10;
        }

        :root {
            /* Philippine Flag Colors */
            --blue: #0038A8;
            --red: #CE1126;
            --yellow: #FCD116;
            --white: #FFFFFF;

            /* Extended Palette */
            --primary: #0038A8;
            --primary-dark: #00257a;
            --primary-light: #004fc7;
            --secondary: #CE1126;
            --accent: #FCD116;
            --gold: #D4A017;

            /* Neutrals */
            --dark: #0f1419;
            --dark-light: #1a2332;
            --dark-blue: #0a1628;
            --gray-100: #f7f9fc;
            --gray-200: #e8ecf1;
            --gray-300: #c4cdd7;
            --gray-400: #8b98a5;
            --gray-500: #5c6b7a;
            --gray-600: #3d4f5f;

            /* Shadows */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.2);
            --shadow-blue: 0 4px 20px rgba(0, 56, 168, 0.3);

            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--blue) 0%, var(--primary-dark) 100%);
            --gradient-accent: linear-gradient(135deg, var(--yellow), var(--gold));
        }

        body {
            background: var(--dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--white);
            line-height: 1.6;
        }

        .admin-logo { max-height: 50px; border-radius: 8px; }
        .admin-wrapper { padding-top: 100px; padding-bottom: 50px; min-height: 100vh; position: relative; z-index: 1; }

        .admin-hero {
            background: linear-gradient(135deg, rgba(0, 56, 168, 0.4) 0%, rgba(0, 37, 122, 0.4) 100%);
            border: 1px solid rgba(252, 209, 22, 0.2);
            border-radius: 0 0 28px 28px;
            padding: 50px 0;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .admin-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at 20% 80%, rgba(252, 209, 22, 0.15) 0%, transparent 50%);
        }

        .admin-hero .container { position: relative; z-index: 1; }
        .admin-hero h1 {
            font-family: 'Playfair Display', serif;
            color: var(--yellow);
            font-size: 2rem;
            margin: 0 0 5px;
        }
        .admin-hero h1 i { color: var(--yellow); margin-right: 10px; }
        .admin-hero p { color: rgba(255,255,255,0.8); margin: 0; }

        .nav-tabs { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .nav-tab {
            padding: 10px 20px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(252, 209, 22, 0.2);
            color: rgba(255,255,255,0.85);
            border-radius: 25px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s var(--ease-smooth);
            font-weight: 500;
        }
        .nav-tab:hover, .nav-tab.active {
            background: rgba(252, 209, 22, 0.15);
            border-color: var(--yellow);
            color: var(--yellow);
        }
        .admin-info { display: flex; align-items: center; gap: 15px; color: white; }
        .admin-info i { color: var(--yellow); }
        .btn-logout {
            padding: 8px 16px;
            background: rgba(206, 17, 38, 0.2);
            border: 1px solid rgba(206, 17, 38, 0.4);
            color: #ff6b6b;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            font-weight: 500;
        }
        .btn-logout:hover {
            background: var(--secondary);
            border-color: var(--secondary);
            color: white;
        }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.4s var(--ease-spring);
        }
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 56, 168, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 56, 168, 0.5);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        .btn-success {
            background: rgba(0, 166, 81, 0.2);
            color: #00a651;
            border: 1px solid rgba(0, 166, 81, 0.3);
        }
        .btn-success:hover {
            background: #00a651;
            color: white;
        }
        .btn-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        .btn-danger:hover {
            background: var(--secondary);
            color: white;
        }
        .btn-warning {
            background: rgba(252, 209, 22, 0.15);
            color: var(--yellow);
            border: 1px solid rgba(252, 209, 22, 0.3);
        }
        .btn-warning:hover {
            background: var(--accent);
            color: var(--dark);
        }
        .btn-sm { padding: 8px 16px; font-size: 0.85rem; }

        /* Cards */
        .card {
            background: rgba(0, 56, 168, 0.15);
            border: 1px solid rgba(252, 209, 22, 0.2);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }
        .card-header {
            background: linear-gradient(135deg, rgba(0, 56, 168, 0.4), rgba(0, 37, 122, 0.4));
            padding: 20px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid rgba(252, 209, 22, 0.15);
        }
        .card-header h2 {
            font-family: 'Playfair Display', serif;
            color: var(--yellow);
            font-size: 1.2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header h2 i { color: var(--yellow); }
        .card-body { padding: 25px; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(0, 56, 168, 0.2);
            border: 1px solid rgba(252, 209, 22, 0.2);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.4s var(--ease-spring);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--yellow);
            box-shadow: 0 10px 30px rgba(252, 209, 22, 0.15);
        }
        .stat-card i { font-size: 2rem; color: var(--yellow); margin-bottom: 10px; }
        .stat-card h3 { font-size: 2rem; color: var(--yellow); margin: 0 0 5px; text-shadow: 0 0 20px rgba(252, 209, 22, 0.3); }
        .stat-card p { color: rgba(255,255,255,0.7); margin: 0; font-size: 0.9rem; }

        /* Tables */
        .table { width: 100%; border-collapse: collapse; }
        .table th {
            padding: 15px;
            text-align: left;
            background: rgba(0, 56, 168, 0.2);
            color: var(--yellow);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(252, 209, 22, 0.3);
        }
        .table td {
            padding: 15px;
            border-bottom: 1px solid rgba(252, 209, 22, 0.1);
            color: rgba(255,255,255,0.85);
        }
        .table tr:hover { background: rgba(0, 56, 168, 0.1); }

        /* Badges */
        .badge {
            display: inline-flex;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success {
            background: rgba(0, 166, 81, 0.2);
            color: #00a651;
            border: 1px solid rgba(0, 166, 81, 0.3);
        }
        .badge-warning {
            background: rgba(252, 209, 22, 0.15);
            color: var(--yellow);
            border: 1px solid rgba(252, 209, 22, 0.3);
        }
        .badge-primary {
            background: var(--gradient-primary);
            color: white;
        }
        .badge-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        .badge-info {
            background: rgba(23, 162, 184, 0.2);
            color: #63d0ef;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }
        .badge-secondary {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Forms */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            color: var(--yellow);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(252, 209, 22, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
            background: rgba(0, 56, 168, 0.2);
            color: var(--white);
        }
        .form-group input::placeholder, .form-group textarea::placeholder {
            color: rgba(255,255,255,0.4);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--yellow);
            box-shadow: 0 0 0 4px rgba(252, 209, 22, 0.1);
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-row { display: flex; gap: 15px; align-items: center; margin-bottom: 15px; }

        /* Toggle Switch */
        .toggle { position: relative; width: 50px; height: 28px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 28px;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            height: 22px; width: 22px;
            left: 3px; bottom: 3px;
            background: var(--yellow);
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle input:checked + .toggle-slider { background: var(--blue); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(22px); }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        .alert-success {
            background: rgba(0, 166, 81, 0.2);
            color: #00a651;
            border: 1px solid rgba(0, 166, 81, 0.3);
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        .alert-warning {
            background: rgba(252, 209, 22, 0.15);
            color: var(--yellow);
            border: 1px solid rgba(252, 209, 22, 0.3);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal.active { display: flex; }
        .modal-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 20, 25, 0.9);
            backdrop-filter: blur(10px);
        }
        .modal-content {
            position: relative;
            background: rgba(0, 56, 168, 0.25);
            border: 1px solid rgba(252, 209, 22, 0.3);
            border-radius: 24px;
            padding: 35px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s;
            backdrop-filter: blur(20px);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(252, 209, 22, 0.2);
        }
        .modal-header h3 {
            color: var(--yellow);
            margin: 0;
            font-family: 'Playfair Display', serif;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-header h3 i { color: var(--yellow); }
        .modal-close {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.7);
        }
        .modal-close:hover {
            background: var(--secondary);
            border-color: var(--secondary);
            color: white;
            transform: rotate(90deg);
        }
        .modal-actions {
            display: flex; gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(252, 209, 22, 0.2);
        }

        /* Tabs */
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: rgba(255,255,255,0.5);
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: rgba(252, 209, 22, 0.3);
        }

        /* Filter Row */
        .filter-row { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-row select {
            padding: 12px 16px;
            border: 2px solid rgba(252, 209, 22, 0.2);
            border-radius: 12px;
            font-size: 0.9rem;
            background: rgba(0, 56, 168, 0.2);
            color: var(--white);
        }
        .filter-row input {
            padding: 12px 16px;
            border: 2px solid rgba(252, 209, 22, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(0, 56, 168, 0.2);
            color: var(--white);
        }
        .filter-row input::placeholder {
            color: rgba(255,255,255,0.4);
        }

        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .card-header { flex-direction: column; align-items: flex-start; }
            .admin-hero h1 { font-size: 1.5rem; }
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
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="../index.html">
                    <div class="nav-logo">
                        <img src="../logo.png" alt="IRECSTEM 2026">
                    </div>
                    <div class="logo-text">
                        <span class="logo-main">IRECSTEM</span>
                        <span class="logo-year">2026</span>
                    </div>
                </a>
            </div>
            <div class="nav-menu">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="admin-info">
                        <i class="fas fa-user-shield"></i>
                        <span><?php echo htmlspecialchars($current_admin['name'] ?? 'Admin'); ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="admin-wrapper">
        <div class="admin-hero">
            <div class="container">
                <h1><i class="fas fa-cog"></i> Admin Panel</h1>
                <p>Manage paper submissions and conference registrations</p>
                <div class="nav-tabs">
                    <button class="nav-tab active" onclick="showTab('dashboard')"><i class="fas fa-chart-line"></i> Dashboard</button>
                    <button class="nav-tab" onclick="showTab('settings')"><i class="fas fa-cogs"></i> Settings</button>
                    <button class="nav-tab" onclick="showTab('registrations')"><i class="fas fa-users"></i> Registrations</button>
                    <button class="nav-tab" onclick="showTab('papers')"><i class="fas fa-file-alt"></i> Papers</button>
                    <button class="nav-tab" onclick="showTab('email')"><i class="fas fa-envelope"></i> Send Email</button>
                    <button class="nav-tab" onclick="showTab('admins')"><i class="fas fa-user-shield"></i> Admins</button>
                </div>
            </div>
        </div>

        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Tab -->
            <div class="tab-content active" id="tab-dashboard">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo count($participants); ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clipboard-list"></i>
                        <h3><?php echo $total_registrations; ?></h3>
                        <p>Conference Registrations</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $pending_registrations; ?></h3>
                        <p>Pending Registrations</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check-circle"></i>
                        <h3><?php echo $approved_registrations; ?></h3>
                        <p>Approved Registrations</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-file-alt"></i>
                        <h3><?php echo $total_papers; ?></h3>
                        <p>Paper Submissions</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half"></i>
                        <h3><?php echo $pending_papers; ?></h3>
                        <p>Pending Papers</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check"></i>
                        <h3><?php echo $accepted_papers; ?></h3>
                        <p>Accepted Papers</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-times"></i>
                        <h3><?php echo $rejected_papers; ?></h3>
                        <p>Rejected Papers</p>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-content" id="tab-settings">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-cogs"></i> Conference Settings</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="save_settings">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Conference Name</label>
                                    <input type="text" name="conference_name" value="<?php echo htmlspecialchars($conference_name); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Conference Date</label>
                                    <input type="text" name="conference_date" value="<?php echo htmlspecialchars($conference_date); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Location</label>
                                    <input type="text" name="conference_location" value="<?php echo htmlspecialchars($conference_location); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Contact Email</label>
                                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Contact Phone</label>
                                    <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($contact_phone); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Registration</label>
                                    <div class="form-row">
                                        <label class="toggle">
                                            <input type="checkbox" name="registration_open" value="1" <?php echo $registration_open === '1' ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span><?php echo $registration_open === '1' ? 'Open' : 'Closed'; ?></span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Registrations Tab -->
            <div class="tab-content" id="tab-registrations">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-users"></i> Conference Registrations</h2>
                        <div style="display: flex; gap: 10px;">
                            <a href="?action=export_registrations" class="btn btn-secondary"><i class="fas fa-download"></i> Export CSV</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="filter-row">
                            <select id="regStatusFilter" onchange="filterRegistrations()">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <input type="text" id="regSearch" placeholder="Search by name or email..." onkeyup="filterRegistrations()" style="flex: 1; padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 10px;">
                        </div>
                        <?php if (count($registrations_list) > 0): ?>
                            <table class="table" id="registrationsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Institution</th>
                                        <th>Category</th>
                                        <th>Country</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrations_list as $r): ?>
                                        <tr data-status="<?php echo $r['status']; ?>">
                                            <td>#<?php echo $r['id']; ?></td>
                                            <td><?php echo htmlspecialchars($r['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($r['user_email']); ?></td>
                                            <td><?php echo htmlspecialchars($r['institution']); ?></td>
                                            <td><span class="badge badge-primary"><?php echo ucfirst($r['category']); ?></span></td>
                                            <td><?php echo htmlspecialchars($r['country']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'cancelled' => 'secondary'
                                                ];
                                                ?>
                                                <span class="badge badge-<?php echo $status_class[$r['status']] ?? 'secondary'; ?>"><?php echo ucfirst($r['status']); ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($r['registered_at'])); ?></td>
                                            <td>
                                                <?php if ($r['status'] === 'pending'): ?>
                                                    <a href="?action=approve_registration&id=<?php echo $r['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this registration?')"><i class="fas fa-check"></i></a>
                                                    <a href="?action=reject_registration&id=<?php echo $r['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this registration?')"><i class="fas fa-times"></i></a>
                                                <?php endif; ?>
                                                <a href="?action=delete_registration&id=<?php echo $r['id']; ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Delete this registration?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No registrations yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Papers Tab -->
            <div class="tab-content" id="tab-papers">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-file-alt"></i> Paper Submissions</h2>
                        <span class="badge badge-primary"><?php echo $total_papers; ?> Papers</span>
                    </div>
                    <div class="card-body">
                        <div class="filter-row">
                            <select id="paperStatusFilter" onchange="filterPapers()">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="under_review">Under Review</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <input type="text" id="paperSearch" placeholder="Search by title or author..." onkeyup="filterPapers()" style="flex: 1; padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 10px;">
                        </div>
                        <?php if (count($papers_list) > 0): ?>
                            <table class="table" id="papersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Track</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($papers_list as $p): ?>
                                        <?php
                                        $author = $db_users->findById($p['user_id'] ?? 0);
                                        $authorName = $author ? ($author['name'] ?? 'N/A') : 'N/A';
                                        ?>
                                        <tr data-status="<?php echo $p['status']; ?>">
                                            <td>#<?php echo $p['id']; ?></td>
                                            <td style="max-width: 250px;">
                                                <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                                                <br><small style="color: #6b7280;"><?php echo htmlspecialchars(substr($p['abstract'], 0, 80)); ?>...</small>
                                            </td>
                                            <td><?php echo htmlspecialchars($authorName); ?></td>
                                            <td><span class="badge badge-primary"><?php echo ucfirst($p['track'] ?? 'N/A'); ?></span></td>
                                            <td>
                                                <?php
                                                $paper_status_class = [
                                                    'pending' => 'warning',
                                                    'under_review' => 'info',
                                                    'accepted' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge badge-<?php echo $paper_status_class[$p['status']] ?? 'secondary'; ?>"><?php echo ucfirst(str_replace('_', ' ', $p['status'])); ?></span>
                                            </td>
                                            <td><?php echo isset($p['created_at']) ? date('M d, Y', strtotime($p['created_at'])) : 'N/A'; ?></td>
                                            <td>
                                                <a href="../paper_file.php?file=<?php echo urlencode($p['file_path']); ?>" class="btn btn-secondary btn-sm" download title="Download Paper">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-primary btn-sm" onclick="showPaperReviewModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['status']); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=delete_paper&id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this paper? This cannot be undone.')" title="Delete Paper">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <p>No papers submitted yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Email Tab -->
            <div class="tab-content" id="tab-email">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-envelope"></i> Send Email</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="emailForm">
                            <input type="hidden" name="action" value="send_email">
                            <div class="form-group">
                                <label>Send To</label>
                                <select name="send_to" id="sendToSelect" onchange="toggleUserSelect()" required>
                                    <option value="all">All Users (<?php echo count($participants); ?>)</option>
                                    <option value="registrations">Approved Registrations Only (<?php echo $approved_registrations; ?>)</option>
                                    <option value="selected">Select Specific Users</option>
                                </select>
                            </div>
                            <div class="form-group" id="userSelectGroup" style="display: none;">
                                <label>Select Users</label>
                                <div style="border: 2px solid #e5e7eb; border-radius: 10px; padding: 15px; max-height: 250px; overflow-y: auto;">
                                    <?php foreach ($participants as $p): ?>
                                        <label style="display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; cursor: pointer;">
                                            <input type="checkbox" name="selected_users[]" value="<?php echo htmlspecialchars($p['email']); ?>" class="user-checkbox">
                                            <span><?php echo htmlspecialchars(($p['name'] ?? 'N/A') . ' - ' . $p['email']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" name="email_subject" required placeholder="Email subject">
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="email_message" required placeholder="Write your message here..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" id="sendBtn"><i class="fas fa-paper-plane"></i> Send Email</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Admins Tab -->
            <div class="tab-content" id="tab-admins">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-shield"></i> Admin Accounts</h2>
                        <button class="btn btn-primary" onclick="showModal('addAdminModal')"><i class="fas fa-plus"></i> Add Admin</button>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $a): ?>
                                    <tr>
                                        <td>#<?php echo $a['id']; ?></td>
                                        <td><?php echo htmlspecialchars($a['name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($a['email']); ?></td>
                                        <td><?php echo isset($a['created_at']) ? date('M d, Y', strtotime($a['created_at'])) : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Paper Review Modal -->
    <div class="modal" id="paperReviewModal">
        <div class="modal-overlay" onclick="hideModal('paperReviewModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-check"></i> Review Paper</h3>
                <button class="modal-close" onclick="hideModal('paperReviewModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_paper_status">
                <input type="hidden" name="paper_id" id="review_paper_id">
                <div class="form-group">
                    <label>Status</label>
                    <select name="new_status" id="review_new_status" required>
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('paperReviewModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal" id="addAdminModal">
        <div class="modal-overlay" onclick="hideModal('addAdminModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-shield"></i> Add Admin</h3>
                <button class="modal-close" onclick="hideModal('addAdminModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_admin">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="admin_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="admin_password" required minlength="6" placeholder="Min 6 characters">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('addAdminModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.classList.add('active');
        }

        function showModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function hideModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }

        function showPaperReviewModal(paperId, currentStatus) {
            document.getElementById('review_paper_id').value = paperId;
            document.getElementById('review_new_status').value = currentStatus;
            document.getElementById('review_admin_notes').value = '';
            showModal('paperReviewModal');
        }

        function toggleUserSelect() {
            const sendTo = document.getElementById('sendToSelect').value;
            const userSelectGroup = document.getElementById('userSelectGroup');
            if (sendTo === 'selected') {
                userSelectGroup.style.display = 'block';
            } else {
                userSelectGroup.style.display = 'none';
            }
        }

        function filterRegistrations() {
            const status = document.getElementById('regStatusFilter').value;
            const search = document.getElementById('regSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#registrationsTable tbody tr');

            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const text = row.textContent.toLowerCase();
                const matchStatus = !status || rowStatus === status;
                const matchSearch = !search || text.includes(search);
                row.style.display = matchStatus && matchSearch ? '' : 'none';
            });
        }

        function filterPapers() {
            const status = document.getElementById('paperStatusFilter').value;
            const search = document.getElementById('paperSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#papersTable tbody tr');

            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const text = row.textContent.toLowerCase();
                const matchStatus = !status || rowStatus === status;
                const matchSearch = !search || text.includes(search);
                row.style.display = matchStatus && matchSearch ? '' : 'none';
            });
        }

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        document.getElementById('emailForm').addEventListener('submit', function(e) {
            const sendTo = document.getElementById('sendToSelect').value;
            if (sendTo === 'selected') {
                const checked = document.querySelectorAll('.user-checkbox:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one user to send the email to.');
                }
            }
        });
    </script>
</body>
</html>
