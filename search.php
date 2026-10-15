<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$q = trim($_GET['q'] ?? '');
$results = ['classes' => [], 'teachers' => []];

if ($q !== '') {
    $like = "%$q%";
    $stmt = $pdo->prepare("SELECT id, title, short_description, image FROM classes WHERE status = 'published' AND (title LIKE ? OR description LIKE ?) LIMIT 10");
    $stmt->execute([$like, $like]);
    $results['classes'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, full_name, qualification, specialization, profile_image FROM teachers WHERE status = 'active' AND (full_name LIKE ? OR bio LIKE ? OR specialization LIKE ?) LIMIT 10");
    $stmt->execute([$like, $like, $like]);
    $results['teachers'] = $stmt->fetchAll();
}

$metaTitle = 'Search Results' . ($q ? ' for "' . e($q) . '"' : '') . ' | ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Search Results</h1>
        <?php if ($q): ?><p class="mb-0">Results for: <strong><?php echo e($q); ?></strong></p><?php endif; ?>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <form action="<?php echo asset('search.php'); ?>" method="GET" class="mb-5">
            <div class="input-group input-group-lg">
                <input type="search" name="q" class="form-control" value="<?php echo e($q); ?>" placeholder="Search classes, teachers..." required>
                <button class="btn btn-sipway" type="submit">Search</button>
            </div>
        </form>

        <?php if ($q === ''): ?>
            <p class="text-muted">Enter a search term to find classes and teachers.</p>
        <?php else: ?>
            <h3 class="mb-3">Classes (<?php echo count($results['classes']); ?>)</h3>
            <?php if (empty($results['classes'])): ?>
                <p class="text-muted mb-4">No classes found.</p>
            <?php else: ?>
                <div class="row g-3 mb-5">
                    <?php foreach ($results['classes'] as $c): ?>
                        <div class="col-md-6">
                            <div class="card sipway-card">
                                <div class="card-body d-flex gap-3">
                                    <img src="<?php echo getImageUrl($c['image']); ?>" width="80" height="60" style="object-fit:cover;border-radius:8px" alt="">
                                    <div>
                                        <h6 class="mb-1"><?php echo e($c['title']); ?></h6>
                                        <p class="small text-muted mb-1"><?php echo e(truncate($c['short_description'], 60)); ?></p>
                                        <a href="<?php echo asset('class-details.php?id=' . $c['id']); ?>" class="small">View Details →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3 class="mb-3">Teachers (<?php echo count($results['teachers']); ?>)</h3>
            <?php if (empty($results['teachers'])): ?>
                <p class="text-muted">No teachers found.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($results['teachers'] as $t): ?>
                        <div class="col-md-6">
                            <div class="card sipway-card">
                                <div class="card-body d-flex gap-3 align-items-center">
                                    <img src="<?php echo getImageUrl($t['profile_image']); ?>" class="rounded-circle" width="60" height="60" style="object-fit:cover" alt="">
                                    <div>
                                        <h6 class="mb-0"><?php echo e($t['full_name']); ?></h6>
                                        <p class="small text-muted mb-1"><?php echo e($t['qualification']); ?></p>
                                        <a href="<?php echo asset('teacher-details.php?id=' . $t['id']); ?>" class="small">View Profile →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
