<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();
$schedules = $pdo->query("SELECT cs.*, c.title as class_title FROM class_schedules cs JOIN classes c ON cs.class_id = c.id ORDER BY c.title, cs.day_of_week")->fetchAll();
$pageTitle = 'Schedules';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-4">Class Schedules</h4>
<p class="text-muted">Schedules are managed when adding/editing classes. Below is an overview.</p>
<div class="admin-card card"><table class="table admin-table mb-0"><thead><tr><th>Class</th><th>Day</th><th>Time</th><th>Notes</th></tr></thead><tbody>
<?php if (empty($schedules)): ?><tr><td colspan="4" class="text-center text-muted">No schedules</td></tr>
<?php else: foreach ($schedules as $s): ?>
<tr><td><?php echo e($s['class_title']); ?></td><td class="text-capitalize"><?php echo e($s['day_of_week']??'—'); ?></td>
<td><?php echo $s['start_time'] ? date('g:i A', strtotime($s['start_time'])) : '—'; ?><?php if ($s['end_time']): ?> - <?php echo date('g:i A', strtotime($s['end_time'])); endif; ?></td>
<td><?php echo e($s['notes']??'—'); ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
