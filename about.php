<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = 'about' AND status = 'published'");
$stmt->execute();
$page = $stmt->fetch();

$metaTitle = ($page['meta_title'] ?? 'About SIPWAY') . ' | ' . SITE_NAME;
$metaDescription = $page['meta_description'] ?? getSetting('meta_description');
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>About SIPWAY</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                <li class="breadcrumb-item active">About</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="mb-5">
                    <?php if ($page && $page['content']): ?>
                        <?php echo $page['content']; ?>
                    <?php else: ?>
                        <p><?php echo e(getSetting('about_intro')); ?></p>
                    <?php endif; ?>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="feature-box text-start">
                            <div class="icon-wrap mx-0 mb-3"><i class="fas fa-eye"></i></div>
                            <h4>Our Vision</h4>
                            <p><?php echo e(getSetting('vision')); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-box text-start">
                            <div class="icon-wrap mx-0 mb-3"><i class="fas fa-bullseye"></i></div>
                            <h4>Our Mission</h4>
                            <p><?php echo e(getSetting('mission')); ?></p>
                        </div>
                    </div>
                </div>

                <h3 class="mb-4">Why Choose SIPWAY</h3>
                <p><?php echo e(getSetting('why_choose_content')); ?></p>

                <div class="row g-4 mt-4">
                    <div class="col-md-4 text-center">
                        <div class="stat-item">
                            <div class="stat-number text-sipway"><?php echo e(getSetting('stat_students')); ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="stat-item">
                            <div class="stat-number text-sipway"><?php echo e(getSetting('stat_teachers')); ?></div>
                            <div class="stat-label">Teachers</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="stat-item">
                            <div class="stat-number text-sipway"><?php echo e(getSetting('stat_years')); ?></div>
                            <div class="stat-label">Years</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
