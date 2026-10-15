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

$stmt = $pdo->prepare("SELECT c.*, t.full_name as teacher_name, t.slug as teacher_slug, t.profile_image as teacher_image,
    t.qualification as teacher_qualification, cat.name as category_name, s.name as subject_name
    FROM classes c
    LEFT JOIN teachers t ON c.teacher_id = t.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN subjects s ON c.subject_id = s.id
    WHERE c.id = ? AND c.status = 'published'");
$stmt->execute([$id]);
$class = $stmt->fetch();

if (!$class) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.php';
    exit;
}

// Schedules
$schedStmt = $pdo->prepare("SELECT * FROM class_schedules WHERE class_id = ? ORDER BY FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday'), start_time");
$schedStmt->execute([$id]);
$schedules = $schedStmt->fetchAll();

$metaTitle = e($class['title']) . ' | ' . SITE_NAME;
$metaDescription = truncate($class['short_description'] ?: $class['description'], 160);
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><?php echo e($class['title']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo asset('classes.php'); ?>">Classes</a></li>
                <li class="breadcrumb-item active"><?php echo e(truncate($class['title'], 40)); ?></li>
            </ol>
        </nav>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <img src="<?php echo getImageUrl($class['image'], 'assets/images/placeholder.png'); ?>" alt="<?php echo e($class['title']); ?>" class="detail-hero-img mb-4">
                
                <h3 class="mb-3">About This Class</h3>
                <div class="mb-4">
                    <?php echo nl2br(e($class['description'])); ?>
                </div>

                <?php if (!empty($schedules)): ?>
                    <h4 class="mb-3">Schedule</h4>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $sch): ?>
                                    <tr>
                                        <td class="text-capitalize"><?php echo e($sch['day_of_week'] ?? formatDate($sch['schedule_date'])); ?></td>
                                        <td>
                                            <?php if ($sch['start_time']): ?>
                                                <?php echo date('g:i A', strtotime($sch['start_time'])); ?>
                                                <?php if ($sch['end_time']): ?> - <?php echo date('g:i A', strtotime($sch['end_time'])); endif; ?>
                                            <?php else: ?>—<?php endif; ?>
                                        </td>
                                        <td><?php echo e($sch['notes'] ?? '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="card sipway-card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Class Information</h5>
                        <ul class="info-list">
                            <?php if ($class['category_name']): ?>
                                <li><span>Category</span><strong><?php echo e($class['category_name']); ?></strong></li>
                            <?php endif; ?>
                            <?php if ($class['subject_name']): ?>
                                <li><span>Subject</span><strong><?php echo e($class['subject_name']); ?></strong></li>
                            <?php endif; ?>
                            <li><span>Type</span><strong class="text-capitalize"><?php echo e($class['class_type']); ?></strong></li>
                            <?php if ($class['duration']): ?>
                                <li><span>Duration</span><strong><?php echo e($class['duration']); ?></strong></li>
                            <?php endif; ?>
                            <?php if ($class['location']): ?>
                                <li><span>Location</span><strong><?php echo e($class['location']); ?></strong></li>
                            <?php endif; ?>
                            <?php if ($class['start_date']): ?>
                                <li><span>Start Date</span><strong><?php echo formatDate($class['start_date']); ?></strong></li>
                            <?php endif; ?>
                            <?php if ($class['fee']): ?>
                                <li><span>Fee</span><strong class="text-sipway"><?php echo e($class['fee_currency'] ?? 'LKR'); ?> <?php echo number_format($class['fee'], 0); ?></strong></li>
                            <?php endif; ?>
                        </ul>
                        <a href="<?php echo asset('contact.php'); ?>" class="btn btn-sipway w-100 mt-3">Inquire Now</a>
                    </div>
                </div>

                <?php if ($class['teacher_name']): ?>
                    <div class="card sipway-card">
                        <div class="card-body text-center">
                            <h5 class="mb-3">Instructor</h5>
                            <img src="<?php echo getImageUrl($class['teacher_image'], 'assets/images/placeholder.png'); ?>" class="rounded-circle mb-2" width="80" height="80" style="object-fit:cover" alt="">
                            <h6><?php echo e($class['teacher_name']); ?></h6>
                            <p class="small text-muted"><?php echo e($class['teacher_qualification']); ?></p>
                            <a href="<?php echo asset('teacher-details.php?id=' . $class['teacher_id']); ?>" class="btn btn-sm btn-outline-sipway">View Profile</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
