<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = 'Please enter username and password.';
        } else {
            $result = attemptLogin($username, $password);
            if ($result['success']) {
                if (!empty($result['must_change'])) {
                    redirect(BASE_URL . '/admin/profile.php?force_change=1', 'Please change your password.', 'warning');
                }
                redirect(BASE_URL . '/admin/dashboard.php', 'Welcome back!');
            } else {
                $error = $result['message'];
            }
        }
    }
}

$logoPath = getSetting('logo_path', 'assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SIPWAY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo asset('assets/css/admin.css'); ?>" rel="stylesheet">
</head>
<body class="admin-login-page">
    <div class="login-card">
        <div class="logo-wrap">
            <img src="<?php echo asset($logoPath); ?>" alt="SIPWAY" onerror="this.outerHTML='<h3 class=\'text-sipway\'>SIPWAY</h3>'">
            <h4 class="mt-2 mb-0">Admin Login</h4>
            <p class="text-muted small">SIPWAY Higher Education</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-admin w-100">Login</button>
        </form>
        <p class="text-center text-muted small mt-3 mb-0">
            <a href="<?php echo asset('index.php'); ?>">← Back to Website</a>
        </p>
    </div>
</body>
</html>
