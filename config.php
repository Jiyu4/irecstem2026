<?php
/**
 * IRECSTEM 2026 - Configuration File
 * JSON File-Based Database (No SQL required)
 */

// Start session
session_start();

// Data directory for JSON files
define('DATA_DIR', __DIR__ . '/data/');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Create directories if not exists
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Application URLs (auto-detect)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '\\' || $basePath === '/') $basePath = '';
define('BASE_URL', $protocol . $host . $basePath . '/');

// Conference Settings
define('CONF_NAME', 'IRECSTEM 2026');
define('CONF_DATE', 'September 15-17, 2026');

// Email Configuration (PHPMailer SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'giomilitar39@gmail.com'); // Change this
define('SMTP_PASSWORD', 'qkfq qsqx rbbj wqyj'); // Change this - use Gmail App Password
define('SMTP_FROM_EMAIL', SMTP_USERNAME); // Use same email as username for reliability
define('SMTP_FROM_NAME', 'IRECSTEM 2026');

// JSON Database Helper Functions
class JsonDB {
    private $file;
    private $data = [];

    public function __construct($collection) {
        $this->file = DATA_DIR . $collection . '.json';
        $this->load();
    }

    private function load() {
        if (file_exists($this->file)) {
            $content = file_get_contents($this->file);
            $this->data = json_decode($content, true) ?: [];
        }
    }

    private function save() {
        file_put_contents($this->file, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function insert(&$item) {
        $item['id'] = count($this->data) + 1;
        $item['created_at'] = date('Y-m-d H:i:s');
        $item['updated_at'] = date('Y-m-d H:i:s');
        $this->data[] = $item;
        $this->save();
        return $item['id'];
    }

    public function update($id, $item) {
        foreach ($this->data as &$row) {
            if ($row['id'] == $id) {
                $item['updated_at'] = date('Y-m-d H:i:s');
                $row = array_merge($row, $item);
                $this->save();
                return true;
            }
        }
        return false;
    }

    public function delete($id) {
        $this->data = array_filter($this->data, function($row) use ($id) {
            return $row['id'] != $id;
        });
        $this->save();
    }

    public function findById($id) {
        foreach ($this->data as $row) {
            if ($row['id'] == $id) {
                return $row;
            }
        }
        return null;
    }

    public function findBy($field, $value) {
        foreach ($this->data as $row) {
            if (isset($row[$field]) && $row[$field] == $value) {
                return $row;
            }
        }
        return null;
    }

    public function findAll($conditions = []) {
        $results = $this->data;
        foreach ($conditions as $field => $value) {
            $results = array_filter($results, function($row) use ($field, $value) {
                return isset($row[$field]) && $row[$field] == $value;
            });
        }
        return array_values($results);
    }

    public function all() {
        return $this->data;
    }

    public function count($conditions = []) {
        return count($this->findAll($conditions));
    }

    public function exists($field, $value) {
        return $this->findBy($field, $value) !== null;
    }

    public function query($callback) {
        return array_filter($this->data, $callback);
    }
}

// Database collections
function users() { return new JsonDB('users'); }
function papers() { return new JsonDB('papers'); }
function registrations() { return new JsonDB('registrations'); }
function settings() { return new JsonDB('settings'); }

/**
 * Get a setting value
 */
function getSetting($key, $default = '') {
    $db = settings();
    $setting = $db->findBy('key', $key);
    return $setting ? $setting['value'] : $default;
}

/**
 * Update or create a setting
 */
function setSetting($key, $value) {
    $db = settings();
    $existing = $db->findBy('key', $key);
    if ($existing) {
        $db->update($existing['id'], ['value' => $value]);
    } else {
        $db->insert(['key' => $key, 'value' => $value]);
    }
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: auth.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: auth.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = users();
    return $db->findById($_SESSION['user_id']);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generateCertificateNumber() {
    return 'IREC2026-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

/**
 * Send email using PHPMailer SMTP
 */
function sendEmail($to, $toName, $subject, $body) {
    require_once __DIR__ . '/vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($to, $toName);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);
    $mail->SMTPDebug = 0;

    return $mail->send();
}

/**
 * Generate verification token
 */
function generateVerificationToken($email) {
    return hash('sha256', $email . time() . uniqid());
}
