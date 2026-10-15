<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$pdo = getDB();
$admin = getCurrentAdmin();
$forceChange = isset($_GET['force_change']);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '') {
            $error = 'Name and email are required.';
        } else {
            $pdo->prepare("UPDATE admins SET full_name = ?, email = ? WHERE id = ?")->execute([$fullName, $email, $admin['id']]);
            
            if ($newPassword !== '') {
                $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
                $stmt->execute([$admin['id']]);
                $row = $stmt->fetch();
                if (!password_verify($currentPassword, $row['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE admins SET password = ?, must_change_password = 0 WHERE id = ?")->execute([$hash, $admin['id']]);
                    logActivity('password_change', 'Password updated');
                    $success = 'Profile and password updated successfully.';
                    $forceChange = false;
                }
            } else {
                if ($forceChange) {
                    $error = 'You must change your password.';
                } else {
                    $success = 'Profile updated successfully.';
                }
            }
            if ($success) {
                logActivity('profile_update', 'Profile updated');
            }
        }
    }
    // Refresh admin
    unset($admin);
    $admin = getCurrentAdmin();
}

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<?php if ($forceChange): ?>
    <div class="alert alert-warning">You must change your password before continuing.</div>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>

<div class="admin-card card" style="max-width: 600px;">
    <div class="card-header">Profile Settings</div>
    <div class="card-body">
        <form method="POST">
            <?php echo csrfField(); ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?php echo e($admin['username'] ?? ''); ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo e($admin['full_name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo e($admin['email'] ?? ''); ?>" required>
            </div>
            <hr>
            <h6>Change Password <?php echo $forceChange ? '(Required)' : '(Optional)'; ?></h6>
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" <?php echo $forceChange ? 'required' : ''; ?>>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" <?php echo $forceChange ? 'required' : ''; ?> minlength="8">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" <?php echo $forceChange ? 'required' : ''; ?>>
            </div>
            <button type="submit" class="btn btn-admin">Save Changes</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
