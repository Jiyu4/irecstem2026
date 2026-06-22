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

// Some pages reference BASE_URL; keep it consistent across redirects
define('BASE_URL', 'http://localhost/akogwapo/');

// Create directories if not exists
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Conference Settings
define('CONF_NAME', 'IRECSTEM 2026');
define('CONF_DATE', 'September 15-17, 2026');

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
function certificates() { return new JsonDB('certificates'); }

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
        header('Location: dashboard.php');
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
