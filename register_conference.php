<?php
/**
 * IRECSTEM 2026 - Conference Registration Handler
 */

require_once 'config.php';
requireLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_conference') {
    $user = getCurrentUser();

    // Check if already registered
    $db = registrations();
    $existing = $db->findBy('user_id', $_SESSION['user_id']);

    if ($existing) {
        $_SESSION['message'] = 'You are already registered for the conference.';
        $_SESSION['message_type'] = 'warning';
        header('Location: dashboard.php');
        exit;
    }

    $data = [
        'user_id' => $_SESSION['user_id'],
        'user_email' => $user['email'],
        'user_name' => sanitize($_POST['name'] ?? $user['name']),
        'institution' => sanitize($_POST['institution'] ?? ''),
        'position' => sanitize($_POST['position'] ?? ''),
        'country' => sanitize($_POST['country'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'category' => sanitize($_POST['category'] ?? 'participant'),
        'requirements' => sanitize($_POST['requirements'] ?? ''),
        'notes' => sanitize($_POST['notes'] ?? ''),
        'status' => 'pending',
        'registered_at' => date('Y-m-d H:i:s')
    ];

    $id = $db->insert($data);

    if ($id) {
        // Send confirmation email
        $email_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #0a0a0f; border-radius: 10px; border: 2px solid #FCD116;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h1 style='color: #FCD116; font-size: 24px; margin: 0;'>IRECSTEM 2026</h1>
                <p style='color: #888; margin: 10px 0 0;'>Conference Registration Submitted</p>
            </div>
            <div style='padding: 20px; color: #fff;'>
                <p>Hello <strong>{$user['name']}</strong>,</p>
                <p>Your conference registration has been submitted successfully!</p>
                <div style='background: rgba(252,209,22,0.1); padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #FCD116;'>
                    <h3 style='color: #FCD116; margin: 0 0 15px;'>Registration Details:</h3>
                    <p><strong>Category:</strong> " . ucfirst($data['category']) . "</p>
                    <p><strong>Institution:</strong> " . htmlspecialchars($data['institution']) . "</p>
                    <p><strong>Status:</strong> <span style='color: #FCD116;'>Pending Approval</span></p>
                </div>
                <p style='color: #888; font-size: 14px;'>You will receive an email notification once your registration is approved by the admin.</p>
            </div>
        </div>";

        sendEmail($user['email'], $user['name'], 'IRECSTEM 2026 - Registration Submitted', $email_body);

        $_SESSION['message'] = 'Registration submitted successfully! You will receive a confirmation email.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to submit registration. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    header('Location: dashboard.php');
    exit;
}

// If not POST, redirect to dashboard
header('Location: dashboard.php');
exit;
