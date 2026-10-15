<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();
$logs = $pdo->query("SELECT l.*, a.username FROM admin_activity_logs l LEFT JOIN admins a ON l.admin_id = a.id ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
$pageTitle = 'Activity Logs';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-4">Activity Logs</h4>
<div class="admin-card card"><table class="table admin-table mb-0"><thead><tr><th>Admin</th><th>Action</th><th>Details</th><th>IP</th><th>Date</th></tr></thead><tbody>
<?php if (empty($logs)): ?><tr><td colspan="5" class="text-center text-muted">No logs</td></tr>
<?php else: foreach ($logs as $l): ?>
<tr><td><?php echo e($l['username']??'—'); ?></td><td><?php echo e($l['action']); ?></td>
<td><?php echo e(truncate($l['details']??'',50)); ?></td><td class="small"><?php echo e($l['ip_address']??''); ?></td>
<td class="small"><?php echo formatDate($l['created_at'], 'd M Y H:i'); ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
