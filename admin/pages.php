<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE pages SET title=?, content=?, meta_title=?, meta_description=?, status=? WHERE id=?")
        ->execute([trim($_POST['title']), $_POST['content'], trim($_POST['meta_title']??''), trim($_POST['meta_description']??''), $_POST['status'], $id]);
    redirect(BASE_URL . '/admin/pages.php', 'Page updated.');
}
$pages = $pdo->query("SELECT * FROM pages ORDER BY title")->fetchAll();
$pageTitle = 'Pages';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-4">Pages</h4>
<?php foreach ($pages as $p): ?>
<div class="admin-card card mb-3"><div class="card-header d-flex justify-content-between"><span><?php echo e($p['title']); ?> <small class="text-muted">(<?php echo e($p['slug']); ?>)</small></span>
<span class="badge badge-status-<?php echo e($p['status']==='published'?'published':'draft'); ?>"><?php echo e($p['status']); ?></span></div>
<div class="card-body">
<form method="POST"><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $p['id']; ?>">
<div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo e($p['title']); ?>"></div>
<div class="mb-2"><label>Content (HTML allowed)</label><textarea name="content" class="form-control" rows="6"><?php echo e($p['content']); ?></textarea></div>
<div class="row g-2 mb-2">
<div class="col-md-5"><label>Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?php echo e($p['meta_title']); ?>"></div>
<div class="col-md-5"><label>Meta Description</label><input type="text" name="meta_description" class="form-control" value="<?php echo e($p['meta_description']); ?>"></div>
<div class="col-md-2"><label>Status</label><select name="status" class="form-select"><option value="published" <?php echo $p['status']==='published'?'selected':''; ?>>Published</option><option value="draft">Draft</option></select></div>
</div>
<button type="submit" class="btn btn-admin btn-sm">Save</button>
</form></div></div>
<?php endforeach; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
