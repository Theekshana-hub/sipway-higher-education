<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        if ($name) {
            $pdo->prepare("INSERT INTO subjects (name, slug, description, status) VALUES (?,?,?,?)")->execute([$name, trim($slug,'-'), trim($_POST['description']??''), $_POST['status']??'active']);
            redirect(BASE_URL . '/admin/subjects.php', 'Subject added.');
        }
    } elseif ($action === 'edit') {
        $pdo->prepare("UPDATE subjects SET name=?, description=?, status=? WHERE id=?")->execute([trim($_POST['name']), trim($_POST['description']??''), $_POST['status'], (int)$_POST['id']]);
        redirect(BASE_URL . '/admin/subjects.php', 'Updated.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM subjects WHERE id=?")->execute([(int)$_POST['id']]);
        redirect(BASE_URL . '/admin/subjects.php', 'Deleted.');
    }
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$pageTitle = 'Subjects';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><h4>Subjects</h4>
<button class="btn btn-admin" data-bs-toggle="modal" data-bs-target="#addModal">Add Subject</button></div>
<div class="admin-card card"><table class="table admin-table mb-0"><thead><tr><th>Name</th><th>Slug</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($subjects as $s): ?>
<tr><td><?php echo e($s['name']); ?></td><td><?php echo e($s['slug']); ?></td>
<td><span class="badge badge-status-<?php echo e($s['status']); ?>"><?php echo e($s['status']); ?></span></td>
<td>
<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?php echo $s['id']; ?>">Edit</button>
<form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><?php echo csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $s['id']; ?>"><button class="btn btn-sm btn-outline-danger">Del</button></form>
</td></tr>
<div class="modal fade" id="edit<?php echo $s['id']; ?>"><div class="modal-dialog"><div class="modal-content"><form method="POST"><?php echo csrfField(); ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?php echo $s['id']; ?>">
<div class="modal-header"><h5>Edit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="text" name="name" class="form-control mb-2" value="<?php echo e($s['name']); ?>" required>
<textarea name="description" class="form-control mb-2"><?php echo e($s['description']); ?></textarea>
<select name="status" class="form-select"><option value="active" <?php echo $s['status']==='active'?'selected':''; ?>>Active</option><option value="inactive">Inactive</option></select>
</div><div class="modal-footer"><button type="submit" class="btn btn-admin">Save</button></div></form></div></div></div>
<?php endforeach; ?>
</tbody></table></div>
<div class="modal fade" id="addModal"><div class="modal-dialog"><div class="modal-content"><form method="POST"><?php echo csrfField(); ?><input type="hidden" name="action" value="add">
<div class="modal-header"><h5>Add Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="text" name="name" class="form-control mb-2" required placeholder="Name">
<textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
<select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
</div><div class="modal-footer"><button type="submit" class="btn btn-admin">Add</button></div></form></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
