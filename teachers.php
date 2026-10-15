
<?php
define('SIPWAY_APP', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// Search and filter
$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR qualification LIKE ?)";
    $params = array_merge(
        $params,
        ["%$search%", "%$search%", "%$search%"]
    );
}

if (in_array($status, ['active', 'inactive'], true)) {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Count teachers
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM teachers WHERE $whereClause"
);
$countStmt->execute($params);

$total = (int)$countStmt->fetchColumn();

// Pagination
$pagination = paginate($total, $perPage, $page);

$limit = (int)$pagination['per_page'];
$offset = (int)$pagination['offset'];

// Fetch teachers
$stmt = $pdo->prepare(
    "SELECT * FROM teachers
     WHERE $whereClause
     ORDER BY sort_order ASC, full_name ASC
     LIMIT $limit OFFSET $offset"
);

$stmt->execute($params);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Teachers';

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Teachers</h4>

    <a href="<?php echo asset('admin/teacher-add.php'); ?>"
       class="btn btn-admin">
        <i class="bi bi-plus-lg"></i> Add Teacher
    </a>
</div>

<!-- Search and filter -->
<form method="GET" class="row g-2 mb-3">

    <div class="col-md-4">
        <input
            type="text"
            name="q"
            class="form-control"
            placeholder="Search..."
            value="<?php echo e($search); ?>"
        >
    </div>

    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All Status</option>

            <option value="active"
                <?php echo $status === 'active' ? 'selected' : ''; ?>>
                Active
            </option>

            <option value="inactive"
                <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                Inactive
            </option>
        </select>
    </div>

    <div class="col-md-2">
        <button type="submit"
                class="btn btn-outline-secondary w-100">
            Filter
        </button>
    </div>

</form>

<!-- Teachers table -->
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

                    <tr>
                        <td colspan="7"
                            class="text-center text-muted py-4">
                            No teachers found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($teachers as $t): ?>

                        <tr>

                            <td>
                                <img
                                    src="<?php echo e(getImageUrl($t['profile_image'])); ?>"
                                    width="40"
                                    height="40"
                                    class="rounded-circle"
                                    style="object-fit:cover"
                                    alt="Teacher"
                                >
                            </td>

                            <td>
                                <strong>
                                    <?php echo e($t['full_name']); ?>
                                </strong>

                                <?php if (!empty($t['email'])): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo e($t['email']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                echo e(truncate($t['qualification'] ?? '', 40));
                                ?>
                            </td>

                            <td>
                                <?php echo (int)$t['experience_years']; ?> yrs
                            </td>

                            <td>
                                <?php
                                $teacherStatus = $t['status'] ?? 'inactive';
                                ?>
                                <span class="badge badge-status-<?php echo e($teacherStatus); ?>">
                                    <?php echo e($teacherStatus); ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($t['featured'])): ?>
                                    <i class="bi bi-star-fill text-warning"></i>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>

                                <a
                                    href="<?php echo asset('admin/teacher-edit.php?id=' . (int)$t['id']); ?>"
                                    class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>

                                <form
                                    action="<?php echo asset('actions/teacher-delete.php'); ?>"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Delete this teacher?');">

                                    <?php echo csrfField(); ?>

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo (int)$t['id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>

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
// Correct pagination path
$baseUrl = asset('teachers.php');

$paginationFile = __DIR__ . '/includes/pagination.php';

if (is_file($paginationFile)) {
    include $paginationFile;
} else {
    // Fallback pagination if the include file is missing
    $totalPages = max(1, (int)ceil($total / $perPage));

    if ($totalPages > 1) {
        echo '<nav aria-label="Teachers pagination">';
        echo '<ul class="pagination justify-content-center mt-4">';

        for ($i = 1; $i <= $totalPages; $i++) {
            $query = http_build_query([
                'q' => $search,
                'status' => $status,
                'page' => $i
            ]);

            $url = $baseUrl . '?' . $query;
            $active = $i === $page ? ' active' : '';

            echo '<li class="page-item' . $active . '">';
            echo '<a class="page-link" href="' .
                 htmlspecialchars($url, ENT_QUOTES, 'UTF-8') .
                 '">' . $i . '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</nav>';
    }
}
?>

<?php
include __DIR__ . '/includes/footer.php';
?>
