<?php
define('SIPWAY_APP', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = ["status = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(full_name LIKE ? OR qualification LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

$limit = (int)$pagination['per_page'];
$offset = (int)$pagination['offset'];

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

<style>
    .teachers-section {
        padding: 60px 0 80px;
    }

    .teachers-search .form-control {
        border-radius: 50px;
        padding: 12px 22px;
        border: 1px solid #eee;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
    }

    .teachers-search .btn-search {
        border-radius: 50px;
        padding: 12px 28px;
        background: #e10613;
        color: #fff;
        font-weight: 600;
        border: none;
    }

    .teachers-search .btn-search:hover {
        background: #b8050f;
        color: #fff;
    }

    .teacher-card {
        background: #fff;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .teacher-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 18px 40px rgba(225, 6, 19, 0.18);
    }

    .teacher-photo {
        position: relative;
        width: 100%;
        aspect-ratio: 4 / 5;
        overflow: hidden;
        border-bottom: 4px solid #e10613;
    }

    .teacher-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }

    .teacher-card:hover .teacher-photo img {
        transform: scale(1.05);
    }

    .teacher-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(160deg, #1c1c1c 0%, #7a0f17 45%, #e10613 100%);
        color: rgba(255, 255, 255, 0.9);
        font-size: 5rem;
    }

    .teacher-featured {
        position: absolute;
        top: 14px;
        right: 14px;
        background: #fff;
        color: #f5a300;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .teacher-info {
        padding: 22px 18px 26px;
        text-align: center;
        flex: 1;
    }

    .teacher-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1c1c1c;
        margin-bottom: 12px;
    }

    .teacher-badge {
        display: inline-block;
        background: #fde8ea;
        color: #e10613;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 8px 20px;
        border-radius: 50px;
    }

    .teacher-experience {
        margin-top: 12px;
        font-size: 0.9rem;
        color: #777;
    }

    .teachers-pagination .page-link {
        color: #e10613;
        border-radius: 50%;
        margin: 0 4px;
        border: 1px solid #eee;
        min-width: 40px;
        text-align: center;
    }

    .teachers-pagination .page-item.active .page-link {
        background: #e10613;
        border-color: #e10613;
        color: #fff;
    }
</style>

<section class="teachers-section">
    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold">Our Teachers</h2>
            <p class="text-muted mb-0">Learn from experienced and dedicated educators</p>
        </div>

        <form method="GET" class="teachers-search row g-2 justify-content-center mb-5">
            <div class="col-md-5">
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Search by name or subject..."
                    value="<?php echo e($search); ?>"
                >
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-search">Search</button>
            </div>
        </form>

        <?php if (empty($teachers)): ?>

            <div class="text-center text-muted py-5">
                <i class="bi bi-person-x" style="font-size:3rem;"></i>
                <p class="mt-3 mb-0">No teachers found.</p>
            </div>

        <?php else: ?>

            <div class="row g-4">

                <?php foreach ($teachers as $t): ?>

                    <div class="col-lg-4 col-md-6">
                        <div class="teacher-card">

                            <div class="teacher-photo">
                                <?php if (!empty($t['profile_image'])): ?>
                                    <img
                                        src="<?php echo e(getImageUrl($t['profile_image'])); ?>"
                                        alt="<?php echo e($t['full_name']); ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="teacher-placeholder">
                                        <i class="bi bi-person-tie"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($t['featured'])): ?>
                                    <span class="teacher-featured">
                                        <i class="bi bi-star-fill"></i>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="teacher-info">
                                <h3 class="teacher-name">
                                    <?php echo e($t['full_name']); ?>
                                </h3>

                                <?php if (!empty($t['qualification'])): ?>
                                    <span class="teacher-badge">
                                        <?php echo e(truncate($t['qualification'], 40)); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ((int)$t['experience_years'] > 0): ?>
                                    <div class="teacher-experience">
                                        <i class="bi bi-award"></i>
                                        <?php echo (int)$t['experience_years']; ?> years experience
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <?php
        $totalPages = max(1, (int)ceil($total / $perPage));
        $baseUrl = asset('teachers.php');

        if ($totalPages > 1):
        ?>
            <nav aria-label="Teachers pagination" class="teachers-pagination">
                <ul class="pagination justify-content-center mt-5">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                        $url = $baseUrl . '?' . http_build_query([
                            'q' => $search,
                            'page' => $i
                        ]);
                        ?>
                        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
                            <a class="page-link" href="<?php echo e($url); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>