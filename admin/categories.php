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
            $pdo->prepare("INSERT INTO categories (name, slug, description, status) VALUES (?,?,?,?)")->execute([$name, trim($slug,'-'), trim($_POST['description']??''), $_POST['status']??'active']);
            logActivity('category_create', $name);
            redirect(BASE_URL . '/admin/categories.php', 'Category added.');
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE categories SET name=?, description=?, status=? WHERE id=?")->execute([trim($_POST['name']), trim($_POST['description']??''), $_POST['status'], $id]);
        redirect(BASE_URL . '/admin/categories.php', 'Updated.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['id']]);
        redirect(BASE_URL . '/admin/categories.php', 'Deleted.');
    }
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM classes WHERE category_id=c.id) as class_count FROM categories c ORDER BY sort_order, name")->fetchAll();
$pageTitle = 'Categories';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><h4>Categories</h4>
<button class="btn btn-admin" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus"></i> Add</button></div>
<div class="admin-card card"><table class="table admin-table mb-0"><thead><tr><th>Name</th><th>Slug</th><th>Classes</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($categories as $c): ?>
<tr><td><?php echo e($c['name']); ?></td><td><?php echo e($c['slug']); ?></td><td><?php echo $c['class_count']; ?></td>
<td><span class="badge badge-status-<?php echo e($c['status']); ?>"><?php echo e($c['status']); ?></span></td>
<td>
<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?php echo $c['id']; ?>">Edit</button>
<form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><?php echo csrfField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button class="btn btn-sm btn-outline-danger">Del</button></form>
</td></tr>
<div class="modal fade" id="edit<?php echo $c['id']; ?>"><div class="modal-dialog"><div class="modal-content"><form method="POST"><?php echo csrfField(); ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?php echo $c['id']; ?>">
<div class="modal-header"><h5>Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label>Name</label><input type="text" name="name" class="form-control" value="<?php echo e($c['name']); ?>" required></div>
<div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo e($c['description']); ?></textarea></div>
<div class="mb-2"><label>Status</label><select name="status" class="form-select"><option value="active" <?php echo $c['status']==='active'?'selected':''; ?>>Active</option><option value="inactive" <?php echo $c['status']==='inactive'?'selected':''; ?>>Inactive</option></select></div>
</div><div class="modal-footer"><button type="submit" class="btn btn-admin">Save</button></div></form></div></div></div>
<?php endforeach; ?>
</tbody></table></div>
<div class="modal fade" id="addModal"><div class="modal-dialog"><div class="modal-content"><form method="POST"><?php echo csrfField(); ?><input type="hidden" name="action" value="add">
<div class="modal-header"><h5>Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-2"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
<div class="mb-2"><label>Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
</div><div class="modal-footer"><button type="submit" class="btn btn-admin">Add</button></div></form></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
