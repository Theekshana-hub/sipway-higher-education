<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . '/admin/subjects.php?msg=deleted');
}

/* ---------- SAVE (Add / Update) ---------- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {

    $id          = (int)($_POST['id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status'] ?? 'active';

    // Auto slug
    if (!$slug && $name) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    if (!$name) {
        $error = 'Subject name is required.';
    }

    if (!$error) {
        $catId = $category_id ?: null;

        if ($id) {
            // Update
            $pdo->prepare("UPDATE subjects SET 
                category_id = ?, name = ?, slug = ?, description = ?, status = ? 
                WHERE id = ?")
                ->execute([$catId, $name, $slug, $description, $status, $id]);
        } else {
            // Insert
            $pdo->prepare("INSERT INTO subjects 
                (category_id, name, slug, description, status) 
                VALUES (?,?,?,?,?)")
                ->execute([$catId, $name, $slug, $description, $status]);
        }

        logActivity($id ? 'subject_update' : 'subject_create', $name);
        redirect(BASE_URL . '/admin/subjects.php?msg=saved');
    }
}

/* ---------- EDIT ---------- */
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

/* ---------- LOAD CATEGORIES (for dropdown) ---------- */
$categories = $pdo->query("
    SELECT c.id, c.name, c.parent_id, p.name AS parent_name
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    WHERE c.status = 'active'
    ORDER BY COALESCE(p.sort_order, c.sort_order), c.sort_order, c.name
")->fetchAll();

// Group categories nicely
$catOptions = [];
foreach ($categories as $cat) {
    if ($cat['parent_id']) {
        $label = $cat['parent_name'] . ' → ' . $cat['name'];
    } else {
        $label = $cat['name'];
    }
    $catOptions[$cat['id']] = $label;
}

/* ---------- LOAD SUBJECTS ---------- */
$subjects = $pdo->query("
    SELECT s.*, 
           c.name AS category_name,
           p.name AS parent_category_name
    FROM subjects s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY s.name
")->fetchAll();

$msgs = [
    'saved'   => ['success', 'Subject saved successfully!'],
    'deleted' => ['success', 'Subject deleted!'],
];

$pageTitle = 'Subjects';
include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'], $msgs[$_GET['msg']])): 
    [$mType, $mText] = $msgs[$_GET['msg']]; ?>
    <div class="alert alert-<?= $mType ?> alert-dismissible fade show">
        <?= e($mText) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ========== FORM ========== -->
    <div class="col-lg-4">
        <div class="admin-card card">
            <div class="card-header">
                <?= $edit ? 'Edit Subject' : 'Add Subject' ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">— Select Category —</option>
                            <?php foreach ($catOptions as $id => $label): ?>
                                <option value="<?= $id ?>" 
                                    <?= ($edit['category_id'] ?? '') == $id ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Subject එක යටතේ තියෙන්න ඕන category එක තෝරන්න</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject Name *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= e($edit['name'] ?? '') ?>" placeholder="e.g. Mathematics">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control"
                               value="<?= e($edit['slug'] ?? '') ?>" placeholder="auto">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= ($edit['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-admin w-100">
                        <?= $edit ? 'Update' : 'Add' ?> Subject
                    </button>

                    <?php if ($edit): ?>
                        <a href="<?= BASE_URL ?>/admin/subjects.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== LIST ========== -->
    <div class="col-lg-8">
        <div class="admin-card card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Subjects (<?= count($subjects) ?>)</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No subjects found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $s): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($s['name']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($s['category_name']): ?>
                                            <?php if ($s['parent_category_name']): ?>
                                                <small class="text-muted"><?= e($s['parent_category_name']) ?> →</small><br>
                                            <?php endif; ?>
                                            <?= e($s['category_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= e($s['slug']) ?></small></td>
                                    <td>
                                        <span class="badge badge-status-<?= e($s['status']) ?>">
                                            <?= e($s['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this subject?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>