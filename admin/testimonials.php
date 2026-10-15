<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pdo->prepare("INSERT INTO testimonials (name, role, content, rating, status) VALUES (?,?,?,?,?)")
            ->execute([trim($_POST['name']), trim($_POST['role']??''), trim($_POST['content']), (int)($_POST['rating']??5), $_POST['status']??'active']);
        redirect(BASE_URL . '/admin/testimonials.php', 'Added.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM testimonials WHERE id=?")->execute([(int)$_POST['id']]);
        redirect(BASE_URL . '/admin/testimonials.php', 'Deleted.');
    }
}
$items = $pdo->query("SELECT * FROM testimonials ORDER BY sort_order, id DESC")->fetchAll();
$pageTitle = 'Testimonials';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><h4>Testimonials</h4>
<button class="btn btn-admin" data-bs-toggle="modal" data-bs-target="#addModal">Add</button></div>
<div class="admin-card card"><table class="table admin-table mb-0"><thead><tr><th>Name</th><th>Role</th><th>Content</th><th>Rating</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($items as $t): ?>
<tr><td><?php echo e($t['name']); ?></td><td><?php echo e($t['role']); ?></td><td><?php echo e(truncate($t['content'],60)); ?></td>
<td><?php echo $t['rating']; ?>★</td><td><span class="badge badge-status-<?php echo e($t['status']); ?>"><?php echo e($t['status']); ?></span></td>
<td><form method="POST" onsubmit="return confirm('Delete?')"><?php echo csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $t['id']; ?>"><button class="btn btn-sm btn-outline-danger">Del</button></form></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="modal fade" id="addModal"><div class="modal-dialog"><div class="modal-content"><form method="POST"><?php echo csrfField(); ?><input type="hidden" name="action" value="add">
<div class="modal-header"><h5>Add Testimonial</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="text" name="name" class="form-control mb-2" placeholder="Name" required>
<input type="text" name="role" class="form-control mb-2" placeholder="Role">
<textarea name="content" class="form-control mb-2" rows="3" required placeholder="Content"></textarea>
<select name="rating" class="form-select mb-2"><?php for($i=5;$i>=1;$i--): ?><option value="<?php echo $i; ?>"><?php echo $i; ?> stars</option><?php endfor; ?></select>
<select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
</div><div class="modal-footer"><button type="submit" class="btn btn-admin">Add</button></div></form></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
