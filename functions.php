<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';


function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}


function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}


function verifyCsrfToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}


function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(generateCsrfToken()) . '">';
}


function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}


function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $msg;
    }
    return null;
}


function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $class = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        return '<div class="alert alert-' . e($class) . ' alert-dismissible fade show" role="alert">'
             . e($flash['message'])
             . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    return '';
}


function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}


function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect(BASE_URL . '/admin/login.php');
    }
}


function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    static $admin = null;
    if ($admin === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, must_change_password FROM admins WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
    }
    return $admin;
}


function getSetting($key, $default = '') {
    static $settings = null;
    if ($settings === null) {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings[$key] ?? $default;
}


function setSetting($key, $value) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([$key, $value]);
}


function getImageUrl($path, $fallback = 'assets/images/placeholder.png') {
    if (empty($path)) {
        return BASE_URL . '/' . $fallback;
    }
    
    $fullPath = BASE_PATH . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        return BASE_URL . '/' . ltrim($path, '/');
    }
    return BASE_URL . '/' . $fallback;
}


function uploadImage($file, $directory, $prefix = 'img_') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred.'];
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'message' => 'File is too large. Max 5MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid image type. Allowed: JPG, PNG, WEBP, GIF.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTS)) {
        return ['success' => false, 'message' => 'Invalid file extension.'];
    }

    $filename = $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
    $targetDir = rtrim($directory, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to save uploaded file.'];
    }

   
    $relative = str_replace(BASE_PATH . '/', '', $targetPath);
    return ['success' => true, 'path' => $relative, 'filename' => $filename];
}


function deleteImage($path) {
    if (empty($path)) return false;
    $fullPath = BASE_PATH . '/' . ltrim($path, '/');
    if (file_exists($fullPath) && is_file($fullPath) && strpos(realpath($fullPath), realpath(UPLOADS_PATH)) === 0) {
        return unlink($fullPath);
    }
    return false;
}


function logActivity($action, $details = '', $entityType = null, $entityId = null) {
    if (!isAdminLoggedIn()) return;
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, entity_type, entity_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['admin_id'],
        $action,
        $details,
        $entityType,
        $entityId,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}


function paginate($total, $perPage = 12, $currentPage = 1) {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}


function buildQueryString($params = [], $exclude = []) {
    $current = $_GET;
    foreach ($exclude as $key) {
        unset($current[$key]);
    }
    $merged = array_merge($current, $params);
    return http_build_query(array_filter($merged, function($v) { return $v !== '' && $v !== null; }));
}


function truncate($text, $length = 150) {
    $text = strip_tags((string)($text ?? ''));
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}


function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}


function asset($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}


function isActive($page) {
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $page ? 'active' : '';
}