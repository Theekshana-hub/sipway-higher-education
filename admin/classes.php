<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$pdo = getDB();
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = "(c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (in_array($status, ['published', 'draft', 'archived'])) {
    $where[] = "c.status = ?";
    $params[] = $status;
}
$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM classes c WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$stmt = $pdo->prepare("SELECT c.*, t.full_name as teacher_name, cat.name as category_name FROM classes c LEFT JOIN teachers t ON c.teacher_id = t.id LEFT JOIN categories cat ON c.category_id = cat.id WHERE $whereClause ORDER BY c.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$classes = $stmt->fetchAll();

$pageTitle = 'Classes';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Classes</h4>
    <a href="<?php echo asset('admin/class-add.php'); ?>" class="btn btn-admin"><i class="bi bi-plus-lg"></i> Add Class</a>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Search..." value="<?php echo e($search); ?>"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
            <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
        </select>
    </div>
    <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
</form>

<div class="admin-card card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Teacher</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No classes found.</td></tr>
                <?php else: foreach ($classes as $c): ?>
                    <tr>
                        <td><img src="<?php echo getImageUrl($c['image']); ?>" width="50" height="35" style="object-fit:cover;border-radius:4px" alt=""></td>
                        <td><strong><?php echo e($c['title']); ?></strong><?php if ($c['featured']): ?> <i class="bi bi-star-fill text-warning"></i><?php endif; ?></td>
                        <td><?php echo e($c['category_name'] ?? '—'); ?></td>
                        <td><?php echo e($c['teacher_name'] ?? '—'); ?></td>
                        <td class="text-capitalize"><?php echo e($c['class_type']); ?></td>
                        <td><span class="badge badge-status-<?php echo e($c['status']); ?>"><?php echo e($c['status']); ?></span></td>
                        <td>
                            <a href="<?php echo asset('admin/class-edit.php?id=' . $c['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="<?php echo asset('actions/class-delete.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this class?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $baseUrl = asset('admin/classes.php'); include __DIR__ . '/../includes/pagination.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
