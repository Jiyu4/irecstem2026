<?php
/**
 * IRECSTEM 2026 - Paper Submission Handler (used by Call for Papers page)
 */

require_once 'config.php';
requireLogin();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: call-for-papers.html');
    exit;
}

$title = sanitize($_POST['paper_title'] ?? '');
$abstract = sanitize($_POST['paper_abstract'] ?? '');
$keywords = sanitize($_POST['paper_keywords'] ?? '');
$track = sanitize($_POST['paper_track'] ?? '');

$file_path = '';

if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['pdf', 'doc', 'docx'];
    $ext = strtolower(pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $filename = $_SESSION['user_id'] . '_' . time() . '.' . $ext;

        // Save inside the same folder convention as dashboard.php
        $paperUploadDir = UPLOAD_DIR . 'papers/';
        if (!is_dir($paperUploadDir)) {
            mkdir($paperUploadDir, 0755, true);
        }

        $targetPath = $paperUploadDir . $filename;
        if (move_uploaded_file($_FILES['paper_file']['tmp_name'], $targetPath)) {
            // store relative path for links (canonical)
            $file_path = 'uploads/papers/' . $filename;
        }

    }
}

if (empty($title) || empty($abstract) || empty($track) || empty($file_path)) {
    // Redirect back with error.
    header('Location: call-for-papers.html?error=1');
    exit;
}

// Determine a user-friendly corresponding author display (optional)
$author = sanitize($_POST['paper_author'] ?? '');
$author_email = sanitize($_POST['paper_author_email'] ?? '');

$paper = [
    'user_id' => $_SESSION['user_id'],
    'title' => $title,
    'abstract' => $abstract,
    'keywords' => $keywords,
    'track' => $track,
    'authors' => $author,
    'file_path' => $file_path,
    'status' => 'pending',
    'submitted_at' => date('Y-m-d H:i:s')
];

$db_papers = papers();
$db_papers->insert($paper);

// Send confirmation email
$user = getCurrentUser();
$email_body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
    <div style='text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #F28C28;'>
        <h1 style='color: #1B3A57; font-size: 24px; margin: 0;'>IRECSTEM 2026</h1>
        <p style='color: #666; margin: 10px 0 0;'>Paper Submission Confirmation</p>
    </div>
    <div style='padding: 20px; color: #333;'>
        <p>Dear <strong>{$user['name']}</strong>,</p>
        <p>Your paper has been submitted successfully!</p>
        <div style='background: #f7f7f7; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3 style='color: #1B3A57; margin: 0 0 15px;'>Paper Details:</h3>
            <p><strong>Title:</strong> " . htmlspecialchars($title) . "</p>
            <p><strong>Track:</strong> " . ucfirst($track) . "</p>
            <p><strong>Status:</strong> <span style='color: #F28C28;'>Pending Review</span></p>
        </div>
        <p>Your paper is now under review. You will receive an email notification once the review is complete.</p>
    </div>
    <div style='background: #1B3A57; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;'>
        <p style='margin: 0;'>September 15-17, 2026 | STATE UNIVERSITY OF NORTHER NEGROS</p>
    </div>
</div>";

try {
    sendEmail($user['email'], $user['name'], 'IRECSTEM 2026 - Paper Submitted Successfully', $email_body);
} catch (Exception $e) {
    // Continue even if email fails
}

// Redirect back to show success modal via existing UI (if it uses JS, it can be added later).
header('Location: dashboard.php');
exit;

