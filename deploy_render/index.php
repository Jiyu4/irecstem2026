<?php
/**
 * IRECSTEM 2026 - Application Entry Point
 * This file routes all requests through the PHP application
 */

// If it's a static HTML file request, serve it directly
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove leading/trailing slashes
$path = trim($path, '/');

// Route HTML pages
$htmlRoutes = [
    '' => 'index.html',
    'about' => 'about.html',
    'call-for-papers' => 'call-for-papers.html',
    'dates' => 'dates.html',
    'contact' => 'contact.html',
    'venue' => 'venue.html',
    'speakers' => 'speakers.html',
    'program' => 'program.html',
    'committee' => 'committee.html',
    'sponsors' => 'sponsors.html',
    'registration' => 'registration.html',
];

// Handle static HTML pages
if ($path === '' || isset($htmlRoutes[$path])) {
    $file = $htmlRoutes[$path] ?? 'index.html';
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($fullPath);
        exit;
    }
}

// Handle API/PHP routes
$phpRoutes = [
    'auth' => 'auth.php',
    'dashboard' => 'dashboard.php',
    'admin' => 'admin/index.php',
    'admin/index' => 'admin/index.php',
    'logout' => 'logout.php',
    'setup-admin' => 'setup-admin.php',
    'paper_file' => 'paper_file.php',
    'submit_paper' => 'submit_paper.php',
    'register_conference' => 'register_conference.php',
    'verify_login' => 'verify_login.php',
];

// Check if it's a PHP route
foreach ($phpRoutes as $route => $file) {
    if ($path === $route || $path === $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            include $fullPath;
            exit;
        }
    }
}

// Check for admin sub-routes
if (strpos($path, 'admin/') === 0) {
    $file = str_replace('admin/', 'admin/', $path);
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        include $fullPath;
        exit;
    }
    // Try with .php extension
    $fullPath = __DIR__ . '/' . $path . '.php';
    if (file_exists($fullPath)) {
        include $fullPath;
        exit;
    }
}

// Default to index.html for unknown routes
$fullPath = __DIR__ . '/index.html';
if (file_exists($fullPath)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($fullPath);
} else {
    http_response_code(404);
    echo '404 - Page Not Found';
}
