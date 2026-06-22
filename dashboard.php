<?php
/**
 * IRECSTEM 2026 - Participant Dashboard (JSON Database)
 */

require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $institution = sanitize($_POST['institution'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $dietary = sanitize($_POST['dietary'] ?? '');
    $participation_type = sanitize($_POST['participation_type'] ?? 'in-person');

    $db = users();
    $update_data = [
        'full_name' => $full_name,
        'institution' => $institution,
        'country' => $country,
        'dietary' => $dietary,
        'participation_type' => $participation_type
    ];

    if ($db->update($_SESSION['user_id'], $update_data)) {
        $message = 'Profile updated successfully!';
        $message_type = 'success';
        $user = getCurrentUser();
    } else {
        $message = 'Failed to update profile.';
        $message_type = 'error';
    }
}

// Handle paper upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_paper') {
    $title = sanitize($_POST['paper_title'] ?? '');
    $abstract = sanitize($_POST['paper_abstract'] ?? '');
    $keywords = sanitize($_POST['paper_keywords'] ?? '');
    $track = sanitize($_POST['paper_track'] ?? '');

    $file_path = '';
    if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] === 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $filename = $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $file_path = 'uploads/' . $filename;
            move_uploaded_file($_FILES['paper_file']['tmp_name'], UPLOAD_DIR . $filename);
        }
    }

    $paper = [
        'user_id' => $_SESSION['user_id'],
        'title' => $title,
        'abstract' => $abstract,
        'keywords' => $keywords,
        'track' => $track,
        'file_path' => $file_path,
        'status' => 'pending'
    ];

    $db_papers = papers();
    if ($db_papers->insert($paper)) {
        $message = 'Paper submitted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to submit paper.';
        $message_type = 'error';
    }
}

// Get user's papers
$papers_db = papers();
$papers = $papers_db->findAll(['user_id' => $_SESSION['user_id']]);

// Get certificate status
$cert_db = certificates();
$certificate = $cert_db->findBy('user_id', $_SESSION['user_id']);

// Check if conference has passed for certificate availability
$conference_date = new DateTime('2026-09-17');
$today = new DateTime();
$certificates_available = $today >= $conference_date;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | IRECSTEM 2026</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Dashboard Container */
        .dashboard-page {
            min-height: 100vh;
            padding-top: 90px;
            padding-bottom: 60px;
            position: relative;
            z-index: 10;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, rgba(0, 56, 168, 0.9), rgba(0, 37, 122, 0.95));
            padding: 40px 0;
            margin-bottom: 40px;
            border-bottom: 3px solid var(--yellow);
        }

        .dashboard-welcome {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-text h1 {
            color: var(--yellow);
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .welcome-text p {
            color: rgba(255, 255, 255, 0.8);
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--yellow);
            color: var(--yellow);
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: var(--yellow);
            color: var(--dark);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
        }

        /* Sidebar */
        .dashboard-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Profile Card */
        .profile-card {
            background: rgba(0, 56, 168, 0.3);
            border: 2px solid rgba(252, 209, 22, 0.2);
            border-radius: 20px;
            padding: 28px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(252, 209, 22, 0.2);
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border: 2px solid var(--yellow);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .profile-info h3 {
            color: white;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }

        .profile-info p {
            color: var(--yellow);
            font-size: 0.85rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .stat-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--yellow);
            display: block;
        }

        .stat-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Quick Links Card */
        .quick-links-card {
            background: rgba(0, 56, 168, 0.3);
            border: 2px solid rgba(252, 209, 22, 0.2);
            border-radius: 20px;
            padding: 24px;
        }

        .quick-links-card h3 {
            color: white;
            font-size: 1rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-links-card h3 i {
            color: var(--yellow);
        }

        .quick-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(252, 209, 22, 0.2);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .quick-link:hover {
            background: rgba(252, 209, 22, 0.15);
            border-color: var(--yellow);
            color: var(--yellow);
            transform: translateX(5px);
        }

        .quick-link i {
            color: var(--yellow);
            width: 20px;
        }

        /* Certificate Card */
        .certificate-card {
            background: rgba(0, 56, 168, 0.3);
            border: 2px solid rgba(252, 209, 22, 0.2);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
        }

        .cert-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            border: 3px solid var(--yellow);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.8rem;
            color: white;
        }

        .certificate-card h3 {
            color: white;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .certificate-card p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            margin-bottom: 16px;
        }

        .cert-number {
            font-family: monospace;
            color: var(--yellow);
            font-size: 0.9rem;
            margin-bottom: 16px;
        }

        /* Main Content */
        .dashboard-main {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Content Card */
        .content-card {
            background: rgba(0, 56, 168, 0.25);
            border: 2px solid rgba(252, 209, 22, 0.15);
            border-radius: 20px;
            padding: 28px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(252, 209, 22, 0.2);
        }

        .card-header h3 {
            color: white;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header h3 i {
            color: var(--yellow);
        }

        /* Form Styles */
        .dash-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .dash-form .form-group {
            display: flex;
            flex-direction: column;
        }

        .dash-form .form-group.full-width {
            grid-column: 1 / -1;
        }

        .dash-form label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dash-form label i {
            color: var(--yellow);
        }

        .dash-input {
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

        .dash-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .dash-input:focus {
            outline: none;
            border-color: var(--blue);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 20px rgba(0, 56, 168, 0.3);
        }

        .dash-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23FCD116' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
        }

        .dash-select option {
            background: var(--dark);
            color: white;
        }

        .dash-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
            border: 2px dashed rgba(252, 209, 22, 0.3);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload:hover {
            border-color: var(--yellow);
            background: rgba(252, 209, 22, 0.1);
        }

        .file-upload input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload i {
            font-size: 2rem;
            color: var(--yellow);
            margin-bottom: 10px;
        }

        .file-upload p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .file-upload span {
            color: var(--yellow);
            font-weight: 500;
        }

        /* Papers List */
        .papers-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .paper-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(252, 209, 22, 0.15);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
        }

        .paper-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(252, 209, 22, 0.3);
            transform: translateX(5px);
        }

        .paper-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
        }

        .paper-title {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            flex: 1;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-pending {
            background: rgba(252, 209, 22, 0.2);
            color: var(--yellow);
            border: 1px solid rgba(252, 209, 22, 0.3);
        }

        .status-reviewing {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .status-accepted {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .paper-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 12px;
        }

        .paper-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }

        .paper-meta i {
            color: var(--yellow);
        }

        .paper-abstract {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        /* Alert */
        .dash-alert {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
        }

        .dash-alert-success {
            background: rgba(0, 166, 81, 0.2);
            border: 1px solid rgba(0, 166, 81, 0.3);
            color: #86efac;
        }

        .dash-alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #fca5a5;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: rgba(252, 209, 22, 0.3);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: white;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-sidebar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-welcome {
                flex-direction: column;
                text-align: center;
            }

            .dash-form {
                grid-template-columns: 1fr;
            }

            .dashboard-sidebar {
                grid-template-columns: 1fr;
            }

            .paper-header {
                flex-direction: column;
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
    <nav class="navbar scrolled" id="navbar">
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
            <div class="dashboard-actions">
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="dashboard-page">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-welcome">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                        <p>Manage your conference registration and paper submissions</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Alerts -->
            <?php if ($message): ?>
            <div class="dash-alert dash-alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo $message; ?></span>
            </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Sidebar -->
                <aside class="dashboard-sidebar">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo count($papers); ?></span>
                                <span class="stat-label">Papers</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $user['participation_type'] === 'in-person' ? 'F2F' : 'Virtual'; ?></span>
                                <span class="stat-label">Mode</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="quick-links-card">
                        <h3><i class="fas fa-link"></i> Quick Links</h3>
                        <div class="quick-links">
                            <a href="venue.html" class="quick-link">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>View Venue</span>
                            </a>
                            <a href="program.html" class="quick-link">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Conference Program</span>
                            </a>
                            <a href="speakers.html" class="quick-link">
                                <i class="fas fa-microphone"></i>
                                <span>Keynote Speakers</span>
                            </a>
                            <a href="contact.html" class="quick-link">
                                <i class="fas fa-headset"></i>
                                <span>Get Support</span>
                            </a>
                        </div>
                    </div>

                    <!-- Certificate -->
                    <div class="certificate-card">
                        <div class="cert-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h3>Certificate</h3>
                        <?php if ($certificates_available && $certificate): ?>
                            <p>Your certificate is ready!</p>
                            <div class="cert-number"><?php echo htmlspecialchars($certificate['certificate_number']); ?></div>
                            <a href="#" class="btn btn-gold btn-sm" onclick="alert('Download coming soon!'); return false;">
                                <i class="fas fa-download"></i> Download
                            </a>
                        <?php elseif ($certificates_available): ?>
                            <p>Processing your certificate...</p>
                        <?php else: ?>
                            <p>Available after Sept 17, 2026</p>
                        <?php endif; ?>
                    </div>
                </aside>

                <!-- Main Content -->
                <div class="dashboard-main">
                    <!-- Edit Profile Form -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
                        </div>
                        <form method="POST" action="" class="dash-form">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" name="full_name" class="dash-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Participation Type</label>
                                <select name="participation_type" class="dash-input dash-select">
                                    <option value="in-person" <?php echo $user['participation_type'] === 'in-person' ? 'selected' : ''; ?>>In-Person</option>
                                    <option value="virtual" <?php echo $user['participation_type'] === 'virtual' ? 'selected' : ''; ?>>Virtual</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Institution</label>
                                <input type="text" name="institution" class="dash-input" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>" placeholder="Your organization">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-globe"></i> Country</label>
                                <input type="text" name="country" class="dash-input" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" placeholder="Your country">
                            </div>

                            <div class="form-group full-width">
                                <label><i class="fas fa-utensils"></i> Dietary Requirements</label>
                                <input type="text" name="dietary" class="dash-input" value="<?php echo htmlspecialchars($user['dietary'] ?? ''); ?>" placeholder="e.g., Vegetarian, None">
                            </div>

                            <div class="form-group full-width">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Submit Paper Form -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-file-upload"></i> Submit Research Paper</h3>
                        </div>
                        <form method="POST" action="" enctype="multipart/form-data" class="dash-form">
                            <input type="hidden" name="action" value="upload_paper">

                            <div class="form-group full-width">
                                <label><i class="fas fa-heading"></i> Paper Title *</label>
                                <input type="text" name="paper_title" class="dash-input" required placeholder="Enter your paper title">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-layer-group"></i> Conference Track *</label>
                                <select name="paper_track" class="dash-input dash-select" required>
                                    <option value="">Select a track</option>
                                    <option value="science">Science</option>
                                    <option value="technology">Technology</option>
                                    <option value="education">Education</option>
                                    <option value="management">Management</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-tags"></i> Keywords</label>
                                <input type="text" name="paper_keywords" class="dash-input" placeholder="e.g., AI, Education, ML">
                            </div>

                            <div class="form-group full-width">
                                <label><i class="fas fa-file-alt"></i> Abstract *</label>
                                <textarea name="paper_abstract" class="dash-input dash-textarea" required placeholder="Enter your paper abstract (200-300 words)"></textarea>
                            </div>

                            <div class="form-group full-width">
                                <div class="file-upload">
                                    <input type="file" name="paper_file" accept=".pdf,.doc,.docx" required>
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p><span>Click to upload</span> or drag and drop</p>
                                    <p style="font-size: 0.8rem; margin-top: 8px;">PDF, DOC, DOCX (Max 10MB)</p>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-paper-plane"></i> Submit Paper
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- My Papers -->
                    <?php if (count($papers) > 0): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-file-alt"></i> My Submissions (<?php echo count($papers); ?>)</h3>
                        </div>
                        <div class="papers-list">
                            <?php foreach ($papers as $paper): ?>
                            <div class="paper-card">
                                <div class="paper-header">
                                    <h4 class="paper-title"><?php echo htmlspecialchars($paper['title']); ?></h4>
                                    <span class="status-badge status-<?php echo $paper['status']; ?>">
                                        <?php echo ucfirst($paper['status']); ?>
                                    </span>
                                </div>
                                <div class="paper-meta">
                                    <span><i class="fas fa-layer-group"></i> <?php echo ucfirst($paper['track']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($paper['created_at'])); ?></span>
                                    <?php if (!empty($paper['keywords'])): ?>
                                    <span><i class="fas fa-tags"></i> <?php echo htmlspecialchars($paper['keywords']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="paper-abstract"><?php echo htmlspecialchars(substr($paper['abstract'], 0, 200)); ?>...</p>
                                <?php if (!empty($paper['file_path'])): ?>
                                <a href="<?php echo htmlspecialchars($paper['file_path']); ?>" class="btn btn-outline btn-sm" target="_blank">
                                    <i class="fas fa-eye"></i> View Paper
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="content-card">
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>No Papers Submitted Yet</h3>
                            <p>Submit your first research paper using the form above.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <button class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="script.js"></script>
</body>
</html>
