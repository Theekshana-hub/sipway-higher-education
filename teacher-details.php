<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ? AND status = 'active'");
$stmt->execute([$id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.php';
    exit;
}

// Subjects
$subStmt = $pdo->prepare("SELECT s.name FROM subjects s INNER JOIN teacher_subjects ts ON s.id = ts.subject_id WHERE ts.teacher_id = ?");
$subStmt->execute([$id]);
$teacherSubjects = $subStmt->fetchAll(PDO::FETCH_COLUMN);

// Related classes
$classStmt = $pdo->prepare("SELECT c.*, cat.name as category_name FROM classes c LEFT JOIN categories cat ON c.category_id = cat.id WHERE c.teacher_id = ? AND c.status = 'published' ORDER BY c.title");
$classStmt->execute([$id]);
$relatedClasses = $classStmt->fetchAll();

$metaTitle = e($teacher['full_name']) . ' | Teachers | ' . SITE_NAME;
$metaDescription = truncate($teacher['bio'] ?? $teacher['qualification'], 160);
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><?php echo e($teacher['full_name']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo asset('teachers.php'); ?>">Teachers</a></li>
                <li class="breadcrumb-item active"><?php echo e($teacher['full_name']); ?></li>
            </ol>
        </nav>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card sipway-card text-center p-4">
                    <img src="<?php echo getImageUrl($teacher['profile_image'], 'assets/images/placeholder.png'); ?>" class="rounded-circle mx-auto mb-3" width="160" height="160" style="object-fit:cover" alt="">
                    <h3><?php echo e($teacher['full_name']); ?></h3>
                    <p class="text-muted"><?php echo e($teacher['qualification']); ?></p>
                    <?php if ($teacher['specialization']): ?>
                        <p><strong>Specialization:</strong> <?php echo e($teacher['specialization']); ?></p>
                    <?php endif; ?>
                    <?php if ($teacher['experience_years']): ?>
                        <p><i class="bi bi-briefcase"></i> <?php echo (int)$teacher['experience_years']; ?>+ years experience</p>
                    <?php endif; ?>
                    <?php if (!empty($teacherSubjects)): ?>
                        <div class="mt-3">
                            <?php foreach ($teacherSubjects as $sub): ?>
                                <span class="badge badge-category me-1 mb-1"><?php echo e($sub); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="social-links mt-3">
                        <?php if ($teacher['linkedin']): ?><a href="<?php echo e($teacher['linkedin']); ?>" target="_blank" class="me-2"><i class="fab fa-linkedin"></i></a><?php endif; ?>
                        <?php if ($teacher['twitter']): ?><a href="<?php echo e($teacher['twitter']); ?>" target="_blank" class="me-2"><i class="fab fa-twitter"></i></a><?php endif; ?>
                        <?php if ($teacher['facebook']): ?><a href="<?php echo e($teacher['facebook']); ?>" target="_blank"><i class="fab fa-facebook"></i></a><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <h3 class="mb-3">Biography</h3>
                <div class="mb-5">
                    <?php echo nl2br(e($teacher['bio'] ?: 'No biography available.')); ?>
                </div>

                <?php if (!empty($relatedClasses)): ?>
                    <h4 class="mb-3">Classes Taught</h4>
                    <div class="row g-3">
                        <?php foreach ($relatedClasses as $class): ?>
                            <div class="col-md-6">
                                <div class="card sipway-card">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo e($class['title']); ?></h6>
                                        <?php if ($class['category_name']): ?>
                                            <span class="badge badge-category mb-2"><?php echo e($class['category_name']); ?></span>
                                        <?php endif; ?>
                                        <a href="<?php echo asset('class-details.php?id=' . $class['id']); ?>" class="btn btn-sm btn-outline-sipway">View Class</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
