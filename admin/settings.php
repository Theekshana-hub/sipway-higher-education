<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pageTitle = 'Settings';
include __DIR__ . '/includes/header.php';
?>
<div class="admin-card card" style="max-width:600px">
<div class="card-header">System Settings</div>
<div class="card-body">
<p>Most website content can be managed from <a href="<?php echo asset('admin/site-content.php'); ?>">Site Content</a>.</p>
<p>For database credentials, edit <code>config.php</code> on the server.</p>
<p class="mb-0"><strong>Environment:</strong> <?php echo e(APP_ENV); ?> | <strong>Debug:</strong> <?php echo APP_DEBUG ? 'On' : 'Off'; ?></p>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
