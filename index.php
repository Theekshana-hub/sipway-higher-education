<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();

$stmt = $pdo->query("SELECT c.*, t.full_name as teacher_name, cat.name as category_name 
    FROM classes c 
    LEFT JOIN teachers t ON c.teacher_id = t.id 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    WHERE c.status = 'published' AND c.featured = 1 
    ORDER BY c.created_at DESC LIMIT 4");
$featuredClasses = $stmt->fetchAll();


$stmt = $pdo->query("SELECT * FROM teachers WHERE status = 'active' AND featured = 1 ORDER BY sort_order ASC LIMIT 4");
$featuredTeachers = $stmt->fetchAll();


$stmt = $pdo->query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order ASC LIMIT 3");
$testimonials = $stmt->fetchAll();


$stmt = $pdo->query("SELECT c.*, t.full_name as teacher_name 
    FROM classes c 
    LEFT JOIN teachers t ON c.teacher_id = t.id 
    WHERE c.status = 'published' AND (c.start_date IS NULL OR c.start_date >= CURDATE()) 
    ORDER BY c.start_date ASC LIMIT 3");
$upcomingClasses = $stmt->fetchAll();

// ---- Hero background slideshow images (assets/images/hero/) ----
$heroSlides = [];
$heroDir = __DIR__ . '/assets/images/hero/';
if (is_dir($heroDir)) {
    $files = glob($heroDir . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
    if ($files) {
        natsort($files);
        foreach ($files as $f) {
            $heroSlides[] = asset('assets/images/hero/' . rawurlencode(basename($f)));
        }
    }
}

$metaTitle = getSetting('meta_title', SITE_NAME);
$metaDescription = getSetting('meta_description');
include __DIR__ . '/includes/header.php';
?>

<style>
/* ===== Hero background slideshow ===== */
.hero-section {
    position: relative;
    overflow: hidden;
}
.hero-section > .container {
    position: relative;
    z-index: 3;
}
.hero-bg-slider {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.hero-bg-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0;
    transform: scale(1);
    transition: opacity 1.6s ease-in-out;
    will-change: opacity, transform;
}
.hero-bg-slide.active {
    opacity: 1;
    animation: heroKenBurns 9s ease-out forwards;
}
@keyframes heroKenBurns {
    from { transform: scale(1); }
    to   { transform: scale(1.12); }
}
/* Dark overlay: images poddak penna, text clear */
.hero-bg-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
        linear-gradient(90deg, rgba(8,8,8,0.88) 0%, rgba(8,8,8,0.68) 50%, rgba(8,8,8,0.50) 100%),
        linear-gradient(180deg, rgba(227,30,54,0.10) 0%, rgba(0,0,0,0.35) 100%);
}
/* Dots */
.hero-bg-dots {
    position: absolute;
    left: 50%;
    bottom: 22px;
    transform: translateX(-50%);
    z-index: 4;
    display: flex;
    gap: 8px;
}
.hero-bg-dots button {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    border: 0;
    padding: 0;
    background: rgba(255,255,255,0.35);
    cursor: pointer;
    transition: all 0.3s ease;
}
.hero-bg-dots button.active {
    width: 26px;
    border-radius: 10px;
    background: #E31E36;
}
@media (prefers-reduced-motion: reduce) {
    .hero-bg-slide.active { animation: none; }
    .hero-bg-slide { transition: none; }
}
</style>


<section class="hero-section">

    <?php if (!empty($heroSlides)): ?>
    <div class="hero-bg-slider" id="heroBgSlider">
        <?php foreach ($heroSlides as $i => $src): ?>
            <div class="hero-bg-slide<?php echo $i === 0 ? ' active' : ''; ?>" style="background-image: url('<?php echo e($src); ?>');"></div>
        <?php endforeach; ?>
    </div>
    <div class="hero-bg-overlay"></div>
    <?php if (count($heroSlides) > 1): ?>
    <div class="hero-bg-dots" id="heroBgDots">
        <?php foreach ($heroSlides as $i => $src): ?>
            <button type="button" aria-label="Slide <?php echo $i + 1; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1><?php echo e(getSetting('hero_heading', 'Shape Your Future with SIPWAY')); ?></h1>
                <p class="lead mb-4"><?php echo e(getSetting('hero_description')); ?></p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?php echo asset(getSetting('hero_cta_primary_link', 'classes.php')); ?>" class="btn btn-sipway">
                        <?php echo e(getSetting('hero_cta_primary', 'Explore Classes')); ?>
                    </a>
                    <a href="<?php echo asset(getSetting('hero_cta_secondary_link', 'teachers.php')); ?>" class="btn btn-outline-light rounded-pill px-4">
                        <?php echo e(getSetting('hero_cta_secondary', 'Meet Our Teachers')); ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-image-wrapper text-center">
                    <?php 
                    $heroImg = getSetting('hero_image');
                    if ($heroImg): ?>
                        <img src="<?php echo getImageUrl($heroImg); ?>" alt="SIPWAY Campus" class="img-fluid">
                    <?php else: ?>
                        <div class="p-5 rounded" style="background: rgba(227,30,54,0.15);">
                            <i class="fas fa-graduation-cap fa-5x text-sipway mb-3"></i>
                            <h3 class="text-white">SIPWAY Higher Education</h3>
                            <p class="text-white-50">Excellence in Learning</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($heroSlides) && count($heroSlides) > 1): ?>
<script>
(function () {
    var slides = document.querySelectorAll('#heroBgSlider .hero-bg-slide');
    var dots   = document.querySelectorAll('#heroBgDots button');
    var current = 0, timer = null, DELAY = 5500;

    // Preload all images so transitions are smooth
    slides.forEach(function (s) {
        var m = s.style.backgroundImage.match(/url\(["']?(.*?)["']?\)/);
        if (m) { var img = new Image(); img.src = m[1]; }
    });

    function show(n) {
        slides[current].classList.remove('active');
        if (dots[current]) dots[current].classList.remove('active');
        current = (n + slides.length) % slides.length;
        // restart Ken Burns animation
        void slides[current].offsetWidth;
        slides[current].classList.add('active');
        if (dots[current]) dots[current].classList.add('active');
    }
    function start() { stop(); timer = setInterval(function () { show(current + 1); }, DELAY); }
    function stop()  { if (timer) clearInterval(timer); }

    dots.forEach(function (d, i) {
        d.addEventListener('click', function () { show(i); start(); });
    });

    // Pause when tab is hidden
    document.addEventListener('visibilitychange', function () {
        document.hidden ? stop() : start();
    });

    start();
})();
</script>
<?php endif; ?>



<section class="section-padding bg-light-gray">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured <span class="highlight">Classes</span></h2>
            <p class="section-subtitle">Explore our most popular programs designed for career success</p>
        </div>
        <div class="row g-4">
            <?php if (empty($featuredClasses)): ?>
                <div class="col-12 text-center text-muted">No featured classes available yet.</div>
            <?php else: ?>
                <?php foreach ($featuredClasses as $class): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card sipway-card">
                            <img src="<?php echo getImageUrl($class['image'], 'assets/images/placeholder.png'); ?>" class="card-img-top" alt="<?php echo e($class['title']); ?>">
                            <div class="card-body">
                                <?php if ($class['category_name']): ?>
                                    <span class="badge badge-category mb-2"><?php echo e($class['category_name']); ?></span>
                                <?php endif; ?>
                                <h5 class="card-title"><?php echo e($class['title']); ?></h5>
                                <p class="card-text small text-muted"><?php echo e(truncate($class['short_description'] ?: $class['description'], 90)); ?></p>
                                <?php if ($class['teacher_name']): ?>
                                    <p class="small mb-2"><i class="bi bi-person"></i> <?php echo e($class['teacher_name']); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo asset('class-details.php?id=' . $class['id']); ?>" class="btn btn-sm btn-sipway">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo asset('classes.php'); ?>" class="btn btn-outline-sipway">View All Classes</a>
        </div>
    </div>
</section>


<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Meet Our <span class="highlight">Teachers</span></h2>
            <p class="section-subtitle">Learn from experienced educators and industry professionals</p>
        </div>
        <div class="row g-4">
            <?php if (empty($featuredTeachers)): ?>
                <div class="col-12 text-center text-muted">No featured teachers available yet.</div>
            <?php else: ?>
                <?php foreach ($featuredTeachers as $teacher): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card sipway-card teacher-card">
                            <img src="<?php echo getImageUrl($teacher['profile_image'], 'assets/images/placeholder.png'); ?>" class="teacher-img mx-auto" alt="<?php echo e($teacher['full_name']); ?>">
                            <h5><?php echo e($teacher['full_name']); ?></h5>
                            <p class="qualification mb-2"><?php echo e($teacher['qualification']); ?></p>
                            <p class="small text-muted"><?php echo e($teacher['specialization']); ?></p>
                            <a href="<?php echo asset('teacher-details.php?id=' . $teacher['id']); ?>" class="btn btn-sm btn-outline-sipway mt-2">View Profile</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo asset('teachers.php'); ?>" class="btn btn-outline-sipway">View All Teachers</a>
        </div>
    </div>
</section>


<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo e(getSetting('stat_students', '2500+')); ?></div>
                    <div class="stat-label">Students</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo e(getSetting('stat_teachers', '85+')); ?></div>
                    <div class="stat-label">Expert Teachers</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo e(getSetting('stat_classes', '120+')); ?></div>
                    <div class="stat-label">Programs</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-number"><?php echo e(getSetting('stat_years', '15+')); ?></div>
                    <div class="stat-label">Years of Excellence</div>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo e(getSetting('why_choose_title', 'Why Choose Our A/L Classes')); ?></h2>
            <p class="section-subtitle"><?php echo e(getSetting('why_choose_content', 'Best guidance and support for Advanced Level students to achieve higher results')); ?></p>
        </div>
  <div class="row g-4">
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
    </div>
</section>


<?php if (!empty($upcomingClasses)): ?>

<?php endif; ?>

<!-- Contact CTA -->
<section class="section-padding bg-light-gray">
    <div class="container text-center">
        <h2 class="section-title mb-3">Ready to Start Your Journey?</h2>
        <p class="mb-4">Contact us today to learn more about our programs and enrollment process.</p>
        <a href="<?php echo asset('contact.php'); ?>" class="btn btn-sipway me-2">Contact Us</a>
        <a href="<?php echo asset('classes.php'); ?>" class="btn btn-outline-sipway">Browse Classes</a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</document_content>