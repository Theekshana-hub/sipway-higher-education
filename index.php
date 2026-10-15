<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();

// Featured classes
$stmt = $pdo->query("SELECT c.*, t.full_name as teacher_name, cat.name as category_name 
    FROM classes c 
    LEFT JOIN teachers t ON c.teacher_id = t.id 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    WHERE c.status = 'published' AND c.featured = 1 
    ORDER BY c.created_at DESC LIMIT 4");
$featuredClasses = $stmt->fetchAll();

// Featured teachers
$stmt = $pdo->query("SELECT * FROM teachers WHERE status = 'active' AND featured = 1 ORDER BY sort_order ASC LIMIT 4");
$featuredTeachers = $stmt->fetchAll();

// Testimonials
$stmt = $pdo->query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order ASC LIMIT 3");
$testimonials = $stmt->fetchAll();

// Upcoming classes
$stmt = $pdo->query("SELECT c.*, t.full_name as teacher_name 
    FROM classes c 
    LEFT JOIN teachers t ON c.teacher_id = t.id 
    WHERE c.status = 'published' AND (c.start_date IS NULL OR c.start_date >= CURDATE()) 
    ORDER BY c.start_date ASC LIMIT 3");
$upcomingClasses = $stmt->fetchAll();

$metaTitle = getSetting('meta_title', SITE_NAME);
$metaDescription = getSetting('meta_description');
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
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

<!-- About Preview -->
<section class="section-padding">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="section-title">About <span class="highlight">SIPWAY</span></h2>
                <p class="mb-4"><?php echo e(getSetting('about_intro')); ?></p>
                <a href="<?php echo asset('about.php'); ?>" class="btn btn-outline-sipway">Learn More About Us</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="feature-box">
                            <div class="icon-wrap"><i class="fas fa-eye"></i></div>
                            <h5>Our Vision</h5>
                            <p class="small mb-0"><?php echo e(truncate(getSetting('vision'), 80)); ?></p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-box">
                            <div class="icon-wrap"><i class="fas fa-bullseye"></i></div>
                            <h5>Our Mission</h5>
                            <p class="small mb-0"><?php echo e(truncate(getSetting('mission'), 80)); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Classes -->
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

<!-- Featured Teachers -->
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

<!-- Stats -->
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

<!-- Why Choose -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo e(getSetting('why_choose_title', 'Why Choose SIPWAY')); ?></h2>
            <p class="section-subtitle"><?php echo e(getSetting('why_choose_content')); ?></p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="icon-wrap"><i class="fas fa-chalkboard-teacher"></i></div>
                    <h5>Expert Faculty</h5>
                    <p class="small mb-0">Learn from highly qualified and experienced educators.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="icon-wrap"><i class="fas fa-laptop-code"></i></div>
                    <h5>Modern Curriculum</h5>
                    <p class="small mb-0">Industry-relevant programs designed for today's job market.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="icon-wrap"><i class="fas fa-users"></i></div>
                    <h5>Student Support</h5>
                    <p class="small mb-0">Dedicated guidance and support throughout your journey.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="icon-wrap"><i class="fas fa-certificate"></i></div>
                    <h5>Recognized Programs</h5>
                    <p class="small mb-0">Quality education that opens doors to career opportunities.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<?php if (!empty($testimonials)): ?>
<section class="section-padding bg-light-gray">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What Our <span class="highlight">Students</span> Say</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($testimonials as $t): ?>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="quote-icon mb-2"><i class="fas fa-quote-left"></i></div>
                        <p class="mb-3"><?php echo e($t['content']); ?></p>
                        <div class="stars mb-2">
                            <?php for ($i = 0; $i < (int)$t['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                        </div>
                        <strong><?php echo e($t['name']); ?></strong>
                        <?php if ($t['role']): ?><br><small class="text-muted"><?php echo e($t['role']); ?></small><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Upcoming Classes -->
<?php if (!empty($upcomingClasses)): ?>
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Upcoming <span class="highlight">Classes</span></h2>
        </div>
        <div class="row g-4">
            <?php foreach ($upcomingClasses as $class): ?>
                <div class="col-md-4">
                    <div class="card sipway-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo e($class['title']); ?></h5>
                            <?php if ($class['start_date']): ?>
                                <p class="small text-muted"><i class="bi bi-calendar"></i> Starts <?php echo formatDate($class['start_date']); ?></p>
                            <?php endif; ?>
                            <?php if ($class['teacher_name']): ?>
                                <p class="small"><i class="bi bi-person"></i> <?php echo e($class['teacher_name']); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo asset('class-details.php?id=' . $class['id']); ?>" class="btn btn-sm btn-sipway">Learn More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
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
