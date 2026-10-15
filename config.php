<?php
/**
 * SIPWAY Higher Education - Configuration
 * Core PHP configuration file
 */

// Prevent direct access
if (!defined('SIPWAY_APP')) {
    define('SIPWAY_APP', true);
}

// Environment
define('APP_ENV', 'development'); // development | production
define('APP_DEBUG', true);

// Base paths
define('BASE_PATH', __DIR__);
define('BASE_URL', '/sipway-higher-education'); // Adjust for your local path, e.g. /sipway-higher-education or empty if in root

// Database credentials (change for production)
define('DB_HOST', 'localhost');
define('DB_NAME', 'sipway_higher_education');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session
define('SESSION_NAME', 'SIPWAY_SESSID');
define('SESSION_LIFETIME', 7200); // 2 hours

// Security
define('CSRF_TOKEN_NAME', 'sipway_csrf_token');
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Uploads
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Paths
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('TEACHERS_UPLOAD', UPLOADS_PATH . '/teachers');
define('CLASSES_UPLOAD', UPLOADS_PATH . '/classes');
define('BANNERS_UPLOAD', UPLOADS_PATH . '/banners');
define('PAGES_UPLOAD', UPLOADS_PATH . '/pages');
define('GALLERY_UPLOAD', UPLOADS_PATH . '/gallery');

// Branding
define('SITE_NAME', 'SIPWAY Higher Education');
define('SITE_TAGLINE', 'Excellence in Higher Education');
define('PRIMARY_COLOR', '#E31E36');
define('SECONDARY_COLOR', '#000000');

// Timezone
date_default_timezone_set('Asia/Colombo');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
