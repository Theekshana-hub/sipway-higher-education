<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Sub-categories තියෙනවා නම් delete කරන්න දෙන්නේ නෑ
    $chk = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
        redirect(BASE_URL . '/admin/categories.php?msg=has_children');
    }

    // Image delete
    $row = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
    $row->execute([$id]);
    $old = $row->fetchColumn();
    if ($old) {
        $path = __DIR__ . '/../uploads/categories/' . $old;
        if (file_exists($path)) @unlink($path);
    }

    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    redirect(BASE_URL . '/admin/categories.php?msg=deleted');
}

/* ---------- SAVE (Add / Update) ---------- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {

    $id        = (int)($_POST['id'] ?? 0);
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');
    $name_si   = trim($_POST['name_si'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $icon      = trim($_POST['icon'] ?? 'fas fa-book');
    $color     = trim($_POST['color'] ?? '#E30613');
    $sort      = (int)($_POST['sort_order'] ?? 0);
    $status    = $_POST['status'] ?? 'active';
    $description = trim($_POST['description'] ?? '');

    // Validate parent
    $parentSlug = '';
    if ($parent_id) {
        if ($parent_id === $id) {
            $parent_id = 0;
        } else {
            $ps = $pdo->prepare("SELECT slug, parent_id FROM categories WHERE id = ?");
            $ps->execute([$parent_id]);
            $p = $ps->fetch();
            if (!$p || $p['parent_id']) {
                $error = 'Invalid parent category. Only main categories can be selected as parent.';
            } else {
                $parentSlug = $p['slug'];
            }
        }
    }

    // Sub-categories තියෙන category එකක් තවත් category එකක් යටතට දාන්න බෑ
    if (!$error && $id && $parent_id) {
        $cc = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $cc->execute([$id]);
        if ($cc->fetchColumn() > 0) {
            $error = 'This category has sub-categories, so it cannot be moved under another category.';
        }
    }

    // Auto slug
    if (!$slug && $name) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
        if ($parentSlug) {
            $slug = $parentSlug . '-' . $slug;
        }
    }

    // Old image
    $oldImage = null;
    if ($id) {
        $st = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $st->execute([$id]);
        $oldImage = $st->fetchColumn();
    }

    if (!$error && !$name) {
        $error = 'Name is required.';
    }

    if (!$error) {
        // Image upload (ඔයාගේ project එකේ upload function එකට අනුව වෙනස් කරගන්න)
        $image = $oldImage; // default

        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/categories/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $newName = uniqid('cat_') . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                    // old image delete
                    if ($oldImage && file_exists($uploadDir . $oldImage)) {
                        @unlink($uploadDir . $oldImage);
                    }
                    $image = $newName;
                } else {
                    $error = 'Image upload failed.';
                }
            } else {
                $error = 'Invalid image. Use JPG/PNG/WebP under 5MB.';
            }
        }

        if (!$error) {
            $pid = $parent_id ?: null;

            if ($id) {
                $pdo->prepare("UPDATE categories SET 
                    parent_id = ?, name = ?, name_si = ?, slug = ?, description = ?,
                    icon = ?, color = ?, image = ?, sort_order = ?, status = ?
                    WHERE id = ?")
                    ->execute([$pid, $name, $name_si, $slug, $description, $icon, $color, $image, $sort, $status, $id]);
            } else {
                $pdo->prepare("INSERT INTO categories 
                    (parent_id, name, name_si, slug, description, icon, color, image, sort_order, status) 
                    VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$pid, $name, $name_si, $slug, $description, $icon, $color, $image, $sort, $status]);
            }

            logActivity($id ? 'category_update' : 'category_create', $name);
            redirect(BASE_URL . '/admin/categories.php?msg=saved');
        }
    }
}

/* ---------- EDIT / PRESELECT ---------- */
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$preParent = (int)($_GET['parent'] ?? 0);

/* ---------- LOAD ALL + TREE ---------- */
$all = $pdo->query("SELECT c.*, 
                    (SELECT COUNT(*) FROM classes WHERE category_id = c.id) AS class_count
                    FROM categories c 
                    ORDER BY sort_order, name")->fetchAll();

$topLevel = [];
$children = [];
foreach ($all as $c) {
    if (empty($c['parent_id'])) {
        $topLevel[] = $c;
    } else {
        $children[$c['parent_id']][] = $c;
    }
}

$editHasChildren = $edit && !empty($children[$edit['id']]);
$selectedParent  = $edit ? (int)($edit['parent_id'] ?? 0) : $preParent;

$msgs = [
    'saved'        => ['success', 'Category saved successfully!'],
    'deleted'      => ['success', 'Category deleted!'],
    'has_children' => ['danger',  'Please delete the sub-categories first.'],
];

$pageTitle = 'Categories';
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
                <?= $edit ? 'Edit Category' : 'Add Category' ?>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" class="form-select" <?= $editHasChildren ? 'disabled' : '' ?>>
                            <option value="0">— None (Main Category) —</option>
                            <?php foreach ($topLevel as $t): 
                                if ($edit && $t['id'] == $edit['id']) continue; ?>
                                <option value="<?= $t['id'] ?>" <?= $selectedParent == $t['id'] ? 'selected' : '' ?>>
                                    <?= e($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <?php if ($editHasChildren): ?>
                                This category has sub-categories, so it stays as main category.
                            <?php else: ?>
                                Sub-category එකක් හදනවා නම් main category එක තෝරන්න.
                            <?php endif; ?>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name (English) *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= e($edit['name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name (Sinhala)</label>
                        <input type="text" name="name_si" class="form-control"
                               value="<?= e($edit['name_si'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control"
                               value="<?= e($edit['slug'] ?? '') ?>" placeholder="auto">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Icon (Font Awesome)</label>
                        <input type="text" name="icon" class="form-control"
                               value="<?= e($edit['icon'] ?? 'fas fa-book') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color w-100"
                               value="<?= e($edit['color'] ?? '#E30613') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category Image</label>
                        <?php if (!empty($edit['image'])): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL ?>/uploads/categories/<?= e($edit['image']) ?>" 
                                     alt="" style="max-height:70px;border-radius:8px">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">JPG / PNG / WebP – max 5MB</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="<?= e($edit['sort_order'] ?? 0) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= ($edit['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-admin w-100">
                        <?= $edit ? 'Update' : 'Add' ?> Category
                    </button>

                    <?php if ($edit || $preParent): ?>
                        <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== LIST ========== -->
    <div class="col-lg-8">
        <div class="admin-card card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Categories (<?= count($all) ?>)</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Sinhala</th>
                            <th>Classes</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        function renderCatRow($c, $isSub, $totalClasses, $subCount) {
                            global $BASE_URL;
                            ?>
                            <tr class="<?= $isSub ? 'table-light' : '' ?>">
                                <td>
                                    <?php if (!empty($c['image'])): ?>
                                        <img src="<?= BASE_URL ?>/uploads/categories/<?= e($c['image']) ?>" 
                                             style="width:40px;height:40px;object-fit:cover;border-radius:8px;<?= $isSub ? 'margin-left:20px' : '' ?>">
                                    <?php else: ?>
                                        <span class="d-inline-flex align-items-center justify-content-center rounded"
                                              style="width:36px;height:36px;background:<?= e($c['color'] ?? '#E30613') ?>;color:#fff;<?= $isSub ? 'margin-left:20px' : '' ?>">
                                            <i class="<?= e($c['icon'] ?? 'fas fa-book') ?>"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isSub): ?>
                                        <i class="bi bi-arrow-return-right text-muted me-1"></i>
                                    <?php endif; ?>
                                    <strong><?= e($c['name']) ?></strong>
                                    <?php if (!$isSub && $subCount): ?>
                                        <span class="badge bg-secondary ms-1"><?= $subCount ?> sub</span>
                                    <?php endif; ?>
                                    <br><small class="text-muted"><?= e($c['slug']) ?></small>
                                </td>
                                <td><?= e($c['name_si'] ?: '-') ?></td>
                                <td><span class="badge bg-info"><?= $totalClasses ?></span></td>
                                <td>
                                    <span class="badge badge-status-<?= e($c['status']) ?>">
                                        <?= e($c['status']) ?>
                                    </span>
                                </td>
                                <td><?= $c['sort_order'] ?></td>
                                <td>
                                    <?php if (!$isSub): ?>
                                        <a href="?parent=<?= $c['id'] ?>" class="btn btn-sm btn-outline-success" title="Add Sub">
                                            <i class="bi bi-plus"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this category?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }

                        foreach ($topLevel as $t):
                            $subs  = $children[$t['id']] ?? [];
                            $total = (int)$t['class_count'];
                            foreach ($subs as $s) $total += (int)$s['class_count'];

                            renderCatRow($t, false, $total, count($subs));
                            foreach ($subs as $s) {
                                renderCatRow($s, true, (int)$s['class_count'], 0);
                            }
                        endforeach;
                        ?>

                        <?php if (empty($all)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No categories found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>