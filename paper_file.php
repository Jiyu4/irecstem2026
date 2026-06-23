<?php
/**
 * Public endpoint to serve a submitted paper attachment safely.
 * Usage: paper_file.php?file=<relative path stored in papers.json>
 */

require_once 'config.php';
requireLogin();

$requested = $_GET['file'] ?? '';

// Sanitize and normalize
$requested = str_replace('\\', '/', $requested);
$requested = preg_replace('/\.\.\//', '', $requested); // Prevent directory traversal

// Only allow uploads paths
if (!preg_match('#^[A-Za-z0-9_\-\./]+\.(?:pdf|doc|docx)$#i', $requested)) {
    http_response_code(404);
    exit('File not found');
}

// Extract just the filename from the path
$filename = basename($requested);

// Search for the file in multiple possible locations
$searchDirs = [
    UPLOAD_DIR,                          // public/uploads/
    UPLOAD_DIR . 'papers/',              // public/uploads/papers/
    dirname(UPLOAD_DIR) . '/',           // public/ (for paths like "papers/filename")
];

$realPath = null;
foreach ($searchDirs as $dir) {
    $testPath = $dir . $filename;
    if (is_file($testPath)) {
        $realPath = $testPath;
        break;
    }
}

if (!$realPath) {
    http_response_code(404);
    exit('File not found');
}

// Authorization check
$canAccess = false;
$db_papers = papers();
$papersAll = $db_papers->all();

foreach ($papersAll as $p) {
    if (empty($p['file_path'])) continue;

    // Match by filename only
    if (basename($p['file_path']) !== $filename) continue;

    // Admin can access all papers
    if (isAdmin()) {
        $canAccess = true;
        break;
    }

    // User must own the paper
    if ((int)($p['user_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)) {
        $canAccess = true;
        break;
    }
}

if (!$canAccess) {
    http_response_code(403);
    exit('Forbidden');
}

// Determine MIME type
$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

// Serve the file with forced download
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($realPath);
exit;