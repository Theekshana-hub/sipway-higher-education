<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = 'about' AND status = 'published'");
$stmt->execute();
$page = $stmt->fetch();

$metaTitle = ($page['meta_title'] ?? 'About Us - A/L Classes') . ' | ' . SITE_NAME;
$metaDescription = $page['meta_description'] ?? getSetting('meta_description');
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>About Our A/L Classes</h1>
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
                        <p class="lead">
                            We are a dedicated Advanced Level (A/L) tuition centre committed to helping students achieve excellent results and secure university entrance.
                        </p>
                        <p>
                            <?php echo e(getSetting('about_intro', 'Our experienced teachers provide complete syllabus coverage, past paper discussions, and personal guidance for A/L students in Science, Commerce, and Arts streams.')); ?>
                        </p>
                    <?php endif; ?>
                </div>

               <div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="feature-box text-start">
            <div class="icon-wrap mx-0 mb-3"><i class="fas fa-eye"></i></div>
            <h4>Our Vision</h4>
            <p>To be the leading A/L tuition centre that empowers every student to achieve outstanding results and gain admission to their desired university through quality hall class education.</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="feature-box text-start">
            <div class="icon-wrap mx-0 mb-3"><i class="fas fa-bullseye"></i></div>
            <h4>Our Mission</h4>
            <p>To provide effective and exam-oriented Advanced Level education through experienced teachers and well-organized hall classes, helping students build strong subject knowledge and exam confidence.</p>
        </div>
    </div>
</div>

                <h3 class="mb-4">Why Choose Our A/L Classes</h3>
                <p><?php echo e(getSetting('why_choose_content', 'We focus only on Advanced Level students and provide everything needed to score high marks in the A/L examination.')); ?></p>

             <div class="row g-4 mt-3">
    <div class="col-md-6 col-lg-3">
        <div class="feature-box">
            <div class="icon-wrap"><i class="fas fa-chalkboard-teacher"></i></div>
            <h5>Experienced A/L Teachers</h5>
            <p class="small mb-0">Subject specialists with years of experience teaching Advanced Level students.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="feature-box">
            <div class="icon-wrap"><i class="fas fa-book-open"></i></div>
            <h5>Exam-Focused Teaching</h5>
            <p class="small mb-0">Complete syllabus coverage with past papers, model questions and marking schemes.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="feature-box">
            <div class="icon-wrap"><i class="fas fa-building"></i></div>
            <h5>Hall Classes</h5>
            <p class="small mb-0">Well-organized large hall classes designed for effective learning and better interaction.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="feature-box">
            <div class="icon-wrap"><i class="fas fa-trophy"></i></div>
            <h5>Proven Results</h5>
            <p class="small mb-0">Many of our students achieve A and B grades and get selected to top universities.</p>
        </div>
    </div>
</div>

        <div class="row g-4 mt-5">
    <div class="col-md-4 text-center">
        <div class="stat-item">
            <div class="stat-number" style="color: #222; font-weight: 700;">
                <?php echo e(getSetting('stat_students', '2500+')); ?>
            </div>
            <div class="stat-label" style="color: #444;">
                A/L Students
            </div>
        </div>
    </div>
    <div class="col-md-4 text-center">
        <div class="stat-item">
            <div class="stat-number" style="color: #222; font-weight: 700;">
                <?php echo e(getSetting('stat_teachers', '85+')); ?>
            </div>
            <div class="stat-label" style="color: #444;">
                Expert Teachers
            </div>
        </div>
    </div>
    <div class="col-md-4 text-center">
        <div class="stat-item">
            <div class="stat-number" style="color: #222; font-weight: 700;">
                <?php echo e(getSetting('stat_years', '15+')); ?>
            </div>
            <div class="stat-label" style="color: #444;">
                Years of Excellence
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>