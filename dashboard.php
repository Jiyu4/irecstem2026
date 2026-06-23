<?php
/**
 * IRECSTEM 2026 - User Dashboard
 */

require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$message = '';
$message_type = '';
$show_paper_form = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $db = users();
        if ($db->update($_SESSION['user_id'], ['name' => $name])) {
            $message = 'Profile updated successfully!';
            $message_type = 'success';
            $_SESSION['user_name'] = $name;
        } else {
            $message = 'Failed to update profile.';
            $message_type = 'error';
        }
    }

    elseif ($action === 'submit_paper') {
        $title = sanitize($_POST['title'] ?? '');
        $abstract = sanitize($_POST['abstract'] ?? '');
        $authors = sanitize($_POST['authors'] ?? '');
        $track = sanitize($_POST['track'] ?? '');

        $file_path = '';
        if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($_FILES['paper_file']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                if (!is_dir(UPLOAD_DIR . 'papers')) {
                    mkdir(UPLOAD_DIR . 'papers', 0755, true);
                }
                $filename = $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['paper_file']['tmp_name'], UPLOAD_DIR . 'papers/' . $filename)) {
                    $file_path = 'papers/' . $filename;
                }
            }
        }

        if (empty($title) || empty($abstract)) {
            $message = 'Title and abstract are required.';
            $message_type = 'error';
        } elseif (empty($file_path)) {
            $message = 'Please attach a paper file (PDF/DOC/DOCX).';
            $message_type = 'error';
        } else {
            $db_papers = papers();
            $paper = [
                'user_id' => $_SESSION['user_id'],
                'title' => $title,
                'abstract' => $abstract,
                'authors' => $authors,
                'track' => $track,
                'file_path' => $file_path,
                'status' => 'pending'
            ];
            $paper_id = $db_papers->insert($paper);

            if ($paper_id) {
                $message = 'Paper submitted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to submit paper.';
                $message_type = 'error';
            }
        }
    }

    elseif ($action === 'delete_paper') {
        $paper_id = intval($_POST['paper_id'] ?? 0);
        if ($paper_id) {
            $db_papers = papers();
            $paper = $db_papers->findById($paper_id);
            if ($paper && $paper['user_id'] == $_SESSION['user_id'] && $paper['status'] === 'pending') {
                // Delete file
                $file = UPLOAD_DIR . $paper['file_path'];
                if (file_exists($file)) {
                    unlink($file);
                }
                $db_papers->delete($paper_id);
                $message = 'Paper deleted successfully.';
                $message_type = 'success';
            }
        }
    }
}

// Get user's papers
$db_papers = papers();
$all_papers = $db_papers->all();
$my_papers = [];
foreach ($all_papers as $p) {
    if (isset($p['user_id']) && $p['user_id'] == $_SESSION['user_id']) {
        $my_papers[] = $p;
    }
}
usort($my_papers, function($a, $b) {
    return strtotime($b['created_at'] ?? $b['submitted_at'] ?? '') - strtotime($a['created_at'] ?? $a['submitted_at'] ?? '');
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | IRECSTEM 2026</title>
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
                <li><a href="program.html" class="nav-link">Program</a></li>
                <li><a href="venue.html" class="nav-link">Venue</a></li>
                <li><a href="contact.html" class="nav-link">Contact</a></li>
                <li class="nav-auth"><a href="dashboard.php" class="btn btn-primary active">Dashboard</a></li>
                <li class="nav-auth"><a href="logout.php" class="btn btn-outline">Logout</a></li>
            </ul>
        </div>
    </nav>

    <section class="section" style="padding-top: 120px;">
        <div class="container">
            <div class="section-header">
                <span class="gov-badge">
                    <i class="fas fa-user-circle"></i>
                    Welcome, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>
                </span>
                <h2 class="section-title" style="margin-top: 20px;">My Dashboard</h2>
            </div>

            <?php if ($message): ?>
            <div class="card" style="max-width: 600px; margin: 0 auto 30px; text-align: center; padding: 20px;">
                <p style="color: <?php echo $message_type === 'success' ? 'var(--success)' : 'var(--error)'; ?>; font-size: 1rem;">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="card" style="max-width: 600px; margin: 0 auto 40px;">
                <h3 style="margin-bottom: 20px; color: var(--yellow);">
                    <i class="fas fa-user"></i> Update Profile
                </h3>
                <form method="POST" action="" style="text-align: left;">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px;">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required
                            style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; color: rgba(255,255,255,0.5); margin-bottom: 8px;">Email (cannot be changed)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                            style="width: 100%; padding: 14px 16px; background: rgba(0,0,0,0.2); border: 2px solid rgba(255,255,255,0.1); border-radius: 12px; color: rgba(255,255,255,0.5); font-size: 1rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Submit Paper Section -->
            <div class="card" style="max-width: 800px; margin: 0 auto 40px;">
                <h3 style="margin-bottom: 20px; color: var(--yellow);">
                    <i class="fas fa-upload"></i> Submit New Paper
                </h3>
                <form method="POST" action="" enctype="multipart/form-data" style="text-align: left;">
                    <input type="hidden" name="action" value="submit_paper">

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px;">Paper Title *</label>
                        <input type="text" name="title" required placeholder="Enter paper title"
                            style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px;">Authors (comma separated)</label>
                        <input type="text" name="authors" placeholder="e.g., John Doe, Jane Smith"
                            style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px;">Track *</label>
                        <select name="track" required style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                            <option value="" style="background: var(--dark);">Select a track</option>
                            <option value="science" style="background: var(--dark);">Science</option>
                            <option value="technology" style="background: var(--dark);">Technology</option>
                            <option value="education" style="background: var(--dark);">Education</option>
                            <option value="management" style="background: var(--dark);">Management</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px;">Abstract *</label>
                        <textarea name="abstract" required placeholder="Enter paper abstract (200-300 words)" rows="5"
                            style="width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem; resize: vertical; font-family: inherit;"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; color: rgba(255,255,255,0.9); margin-bottom: 8px;">Upload Paper (PDF/DOC/DOCX) *</label>
                        <input type="file" name="paper_file" accept=".pdf,.doc,.docx" required
                            style="width: 100%; padding: 12px; background: rgba(255,255,255,0.1); border: 2px dashed rgba(255,209,22,0.3); border-radius: 12px; color: white; font-size: 1rem;">
                        <small style="color: rgba(255,255,255,0.5); margin-top: 5px; display: block;">Max file size: 10MB</small>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Submit Paper
                    </button>
                </form>
            </div>

            <!-- My Papers Section -->
            <div class="card" style="max-width: 100%;">
                <h3 style="margin-bottom: 25px; color: var(--yellow);">
                    <i class="fas fa-file-alt"></i> My Submitted Papers
                </h3>

                <?php if (count($my_papers) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; color: rgba(255,255,255,0.9);">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,209,22,0.3);">
                                <th style="padding: 15px 10px; text-align: left; color: var(--yellow);">Title</th>
                                <th style="padding: 15px 10px; text-align: left; color: var(--yellow);">Track</th>
                                <th style="padding: 15px 10px; text-align: left; color: var(--yellow);">Submitted</th>
                                <th style="padding: 15px 10px; text-align: center; color: var(--yellow);">Status</th>
                                <th style="padding: 15px 10px; text-align: center; color: var(--yellow);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_papers as $paper): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <td style="padding: 15px 10px;">
                                    <strong><?php echo htmlspecialchars($paper['title']); ?></strong>
                                    <?php if (!empty($paper['authors'])): ?>
                                    <br><small style="color: rgba(255,255,255,0.5);">by <?php echo htmlspecialchars($paper['authors']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 10px; text-transform: capitalize;"><?php echo $paper['track'] ?? 'N/A'; ?></td>
                                <td style="padding: 15px 10px; font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($paper['created_at'] ?? $paper['submitted_at'] ?? '')); ?></td>
                                <td style="padding: 15px 10px; text-align: center;">
                                    <span class="status-badge status-<?php echo $paper['status'] ?? 'pending'; ?>" style="display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize;
                                        background: <?php
                                            $status = $paper['status'] ?? 'pending';
                                            if ($status === 'approved') echo 'rgba(0,166,81,0.2); color: #4ade80; border: 1px solid #00a651;';
                                            elseif ($status === 'rejected') echo 'rgba(220,53,69,0.2); color: #f87171; border: 1px solid #dc3545;';
                                            elseif ($status === 'under_review') echo 'rgba(23,162,184,0.2); color: #38bdf8; border: 1px solid #17a2b8;';
                                            else echo 'rgba(252,209,22,0.2); color: var(--yellow); border: 1px solid rgba(252,209,22,0.5);';
                                        ?>">
                                        <?php echo str_replace('_', ' ', $paper['status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 10px; text-align: center; white-space: nowrap;">
                                    <?php if (!empty($paper['file_path'])): ?>
                                    <a href="uploads/<?php echo $paper['file_path']; ?>" download class="btn btn-sm" style="padding: 6px 12px; font-size: 0.8rem; background: var(--primary); color: white; border-radius: 8px; margin-right: 5px; display: inline-block; text-decoration: none;">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <?php endif; ?>
                                    <?php if (($paper['status'] ?? '') === 'pending'): ?>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this paper?');">
                                        <input type="hidden" name="action" value="delete_paper">
                                        <input type="hidden" name="paper_id" value="<?php echo $paper['id']; ?>">
                                        <button type="submit" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(220,53,69,0.2); color: #f87171; border: 1px solid #dc3545; border-radius: 8px; cursor: pointer;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state" style="text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.05); border-radius: 16px; color: rgba(255,255,255,0.5);">
                    <i class="fas fa-file-alt" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p style="font-size: 1.1rem; margin-bottom: 10px;">No papers submitted yet</p>
                    <p>Submit your first paper using the form above!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 IRECSTEM 2026. All rights reserved. | A project of DOST-STII</p>
            </div>
        </div>
    </footer>

    <button class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="script.js"></script>
</body>
</html>
