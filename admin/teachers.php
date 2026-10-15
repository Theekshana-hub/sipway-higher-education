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
    $where[] = "(full_name LIKE ? OR email LIKE ? OR qualification LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if (in_array($status, ['active', 'inactive'])) {
    $where[] = "status = ?";
    $params[] = $status;
}
$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

$stmt = $pdo->prepare("SELECT * FROM teachers WHERE $whereClause ORDER BY sort_order ASC, full_name ASC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$pageTitle = 'Teachers';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Teachers</h4>
    <a href="<?php echo asset('admin/teacher-add.php'); ?>" class="btn btn-admin"><i class="bi bi-plus-lg"></i> Add Teacher</a>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Search..." value="<?php echo e($search); ?>">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
    </div>
</form>

<div class="admin-card card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Qualification</th>
                    <th>Experience</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No teachers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($teachers as $t): ?>
                        <tr>
                            <td>
                                <img src="<?php echo getImageUrl($t['profile_image']); ?>" width="40" height="40" class="rounded-circle" style="object-fit:cover" alt="">
                            </td>
                            <td>
                                <strong><?php echo e($t['full_name']); ?></strong>
                                <?php if ($t['email']): ?><br><small class="text-muted"><?php echo e($t['email']); ?></small><?php endif; ?>
                            </td>
                            <td><?php echo e(truncate($t['qualification'], 40)); ?></td>
                            <td><?php echo (int)$t['experience_years']; ?> yrs</td>
                            <td><span class="badge badge-status-<?php echo e($t['status']); ?>"><?php echo e($t['status']); ?></span></td>
                            <td><?php echo $t['featured'] ? '<i class="bi bi-star-fill text-warning"></i>' : '—'; ?></td>
                            <td>
                                <a href="<?php echo asset('admin/teacher-edit.php?id=' . $t['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="<?php echo asset('actions/teacher-delete.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this teacher?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php 
$baseUrl = asset('admin/teachers.php');
include __DIR__ . '/../includes/pagination.php'; 
?>

<?php include __DIR__ . '/includes/footer.php'; ?>
