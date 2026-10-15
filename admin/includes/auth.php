<?php
/**
 * Admin authentication helper
 */
if (!defined('SIPWAY_APP')) {
    define('SIPWAY_APP', true);
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions.php';

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        redirect(BASE_URL . '/admin/login.php');
    }
    // Force password change if required
    $admin = getCurrentAdmin();
    if ($admin && $admin['must_change_password'] && basename($_SERVER['PHP_SELF']) !== 'profile.php') {
        redirect(BASE_URL . '/admin/profile.php?force_change=1');
    }
}

function attemptLogin($username, $password) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Check lockout
    if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
        $mins = ceil((strtotime($admin['locked_until']) - time()) / 60);
        return ['success' => false, 'message' => "Account locked. Try again in $mins minutes."];
    }

    if (!password_verify($password, $admin['password'])) {
        $attempts = $admin['login_attempts'] + 1;
        $lockedUntil = null;
        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            $attempts = 0;
        }
        $upd = $pdo->prepare("UPDATE admins SET login_attempts = ?, locked_until = ? WHERE id = ?");
        $upd->execute([$attempts, $lockedUntil, $admin['id']]);
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Success
    $pdo->prepare("UPDATE admins SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
    
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role'] = $admin['role'];

    logActivity('login', 'Admin logged in');
    return ['success' => true, 'must_change' => (bool)$admin['must_change_password']];
}
