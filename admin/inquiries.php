<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();
$items = $pdo->query("SELECT i.*, c.title as class_title FROM student_inquiries i LEFT JOIN classes c ON i.class_id = c.id ORDER BY i.created_at DESC LIMIT 50")->fetchAll();
$pageTitle = 'Student Inquiries';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-4">Student Inquiries</h4>
<div class="admin-card card"><table class="table admin-table mb-0"><thead><tr><th>Name</th><th>Email</th><th>Class</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php if (empty($items)): ?><tr><td colspan="5" class="text-center text-muted">No inquiries yet</td></tr>
<?php else: foreach ($items as $i): ?>
<tr><td><?php echo e($i['name']); ?></td><td><?php echo e($i['email']); ?></td><td><?php echo e($i['class_title']??'—'); ?></td>
<td><span class="badge badge-status-<?php echo e($i['status']); ?>"><?php echo e($i['status']); ?></span></td>
<td><?php echo formatDate($i['created_at']); ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
