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

$hasTeacher = !empty($class['teacher_name']);
?>

<style>
    .cd-wrap {
        --cd-accent: var(--sipway-primary, #0d6efd);
        --cd-ink: #1f2430;
        --cd-muted: #6b7280;
        --cd-line: #e8eaf0;
        --cd-soft: #f5f7fb;
        --cd-radius: 16px;
    }

    /* ---------- Header ---------- */
    .cd-header {
        padding: 2.5rem 0 2rem;
        background: var(--cd-soft);
        border-bottom: 1px solid var(--cd-line);
    }
    .cd-header .breadcrumb { margin-bottom: 1rem; font-size: .875rem; }
    .cd-title {
        font-size: clamp(1.75rem, 3.2vw, 2.6rem);
        font-weight: 700;
        line-height: 1.2;
        color: var(--cd-ink);
        margin-bottom: 1rem;
        max-width: 22ch;
    }
    @media (min-width: 992px) { .cd-title { max-width: 30ch; } }

    .cd-badges { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.25rem; }
    .cd-badge {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .35rem .8rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--cd-line);
        font-size: .85rem;
        color: var(--cd-ink);
    }
    .cd-badge i { color: var(--cd-accent); }

    /* Instructor chip in header (teacher visible at the top) */
    .cd-teacher-chip {
        display: inline-flex; align-items: center; gap: .75rem;
        padding: .5rem 1rem .5rem .5rem;
        background: #fff;
        border: 1px solid var(--cd-line);
        border-radius: 999px;
        text-decoration: none;
        color: var(--cd-ink);
    }
    .cd-teacher-chip:hover { border-color: var(--cd-accent); color: var(--cd-ink); }
    .cd-teacher-chip img {
        width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
    }
    .cd-teacher-chip small { display: block; color: var(--cd-muted); font-size: .75rem; line-height: 1.1; }
    .cd-teacher-chip strong { font-size: .95rem; line-height: 1.2; }

    /* ---------- Body ---------- */
    .cd-body { padding: 2.5rem 0 4rem; }
    .cd-hero-img {
        width: 100%;
        aspect-ratio: 16 / 9;
        object-fit: cover;
        border-radius: var(--cd-radius);
        display: block;
        margin-bottom: 2rem;
        background: var(--cd-soft);
    }
    .cd-section-title {
        font-size: 1.3rem; font-weight: 700; color: var(--cd-ink);
        margin-bottom: 1rem;
    }
    .cd-description {
        color: #374151; line-height: 1.8; font-size: 1.02rem;
        max-width: 70ch; margin-bottom: 2.5rem;
    }

    /* Schedule */
    .cd-schedule { display: grid; gap: .75rem; margin-bottom: 1rem; }
    .cd-sch-row {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 1rem;
        align-items: center;
        padding: 1rem 1.25rem;
        background: #fff;
        border: 1px solid var(--cd-line);
        border-left: 4px solid var(--cd-accent);
        border-radius: 12px;
    }
    .cd-sch-day { font-weight: 700; text-transform: capitalize; color: var(--cd-ink); }
    .cd-sch-time { color: var(--cd-ink); }
    .cd-sch-note { display: block; color: var(--cd-muted); font-size: .875rem; margin-top: .15rem; }
    @media (max-width: 575.98px) {
        .cd-sch-row { grid-template-columns: 1fr; gap: .25rem; }
    }

    /* ---------- Sidebar ---------- */
    .cd-side { position: sticky; top: 90px; display: grid; gap: 1.25rem; }
    @media (max-width: 991.98px) { .cd-side { position: static; margin-bottom: 2rem; } }

    .cd-card {
        background: #fff;
        border: 1px solid var(--cd-line);
        border-radius: var(--cd-radius);
        padding: 1.5rem;
    }
    .cd-card h5 { font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; color: var(--cd-ink); }

    .cd-instructor { text-align: center; }
    .cd-instructor img {
        width: 96px; height: 96px; border-radius: 50%; object-fit: cover;
        margin-bottom: .75rem;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px var(--cd-accent);
    }
    .cd-instructor h6 { font-size: 1.1rem; font-weight: 700; margin-bottom: .25rem; }
    .cd-instructor p { color: var(--cd-muted); font-size: .9rem; margin-bottom: 1rem; }

    .cd-info { list-style: none; padding: 0; margin: 0; }
    .cd-info li {
        display: flex; justify-content: space-between; gap: 1rem;
        padding: .7rem 0;
        border-bottom: 1px solid var(--cd-line);
        font-size: .93rem;
    }
    .cd-info li:last-child { border-bottom: 0; }
    .cd-info span { color: var(--cd-muted); }
    .cd-info strong { text-align: right; color: var(--cd-ink); }

    .cd-fee {
        display: flex; justify-content: space-between; align-items: baseline;
        padding: 1rem 0 .25rem;
    }
    .cd-fee span { color: var(--cd-muted); font-size: .9rem; }
    .cd-fee strong { font-size: 1.5rem; color: var(--cd-accent); }
</style>

<div class="cd-wrap">

    <!-- ================= HEADER ================= -->
    <section class="cd-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo asset('classes.php'); ?>">Classes</a></li>
                    <li class="breadcrumb-item active"><?php echo e(truncate($class['title'], 40)); ?></li>
                </ol>
            </nav>

            <h1 class="cd-title"><?php echo e($class['title']); ?></h1>

            <div class="cd-badges">
                <?php if ($class['category_name']): ?>
                    <span class="cd-badge"><i class="bi bi-tag"></i><?php echo e($class['category_name']); ?></span>
                <?php endif; ?>
                <?php if ($class['subject_name']): ?>
                    <span class="cd-badge"><i class="bi bi-book"></i><?php echo e($class['subject_name']); ?></span>
                <?php endif; ?>
                <?php if ($class['class_type']): ?>
                    <span class="cd-badge text-capitalize"><i class="bi bi-laptop"></i><?php echo e($class['class_type']); ?></span>
                <?php endif; ?>
                <?php if ($class['duration']): ?>
                    <span class="cd-badge"><i class="bi bi-clock"></i><?php echo e($class['duration']); ?></span>
                <?php endif; ?>
                <?php if ($class['location']): ?>
                    <span class="cd-badge"><i class="bi bi-geo-alt"></i><?php echo e($class['location']); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($hasTeacher): ?>
                <a class="cd-teacher-chip" href="<?php echo asset('teacher-details.php?id=' . $class['teacher_id']); ?>">
                    <img src="<?php echo getImageUrl($class['teacher_image'], 'assets/images/placeholder.png'); ?>" alt="<?php echo e($class['teacher_name']); ?>">
                    <span>
                        <small>Taught by</small>
                        <strong><?php echo e($class['teacher_name']); ?></strong>
                    </span>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= BODY ================= -->
    <section class="cd-body">
        <div class="container">
            <div class="row g-4 g-xl-5">

                <!-- Sidebar: on mobile it appears FIRST (order-first), on desktop on the right -->
                <div class="col-lg-4 order-first order-lg-last">
                    <aside class="cd-side">

                        <?php if ($hasTeacher): ?>
                            <div class="cd-card cd-instructor">
                                <h5>Your instructor</h5>
                                <img src="<?php echo getImageUrl($class['teacher_image'], 'assets/images/placeholder.png'); ?>" alt="<?php echo e($class['teacher_name']); ?>">
                                <h6><?php echo e($class['teacher_name']); ?></h6>
                                <?php if (!empty($class['teacher_qualification'])): ?>
                                    <p><?php echo e($class['teacher_qualification']); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo asset('teacher-details.php?id=' . $class['teacher_id']); ?>" class="btn btn-sm btn-outline-sipway">View profile</a>
                            </div>
                        <?php endif; ?>

                        <div class="cd-card">
                            <h5>Class information</h5>
                            <ul class="cd-info">
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
                                    <li><span>Start date</span><strong><?php echo formatDate($class['start_date']); ?></strong></li>
                                <?php endif; ?>
                            </ul>

                            <?php if ($class['fee']): ?>
                                <div class="cd-fee">
                                    <span>Fee</span>
                                    <strong><?php echo e($class['fee_currency'] ?? 'LKR'); ?> <?php echo number_format($class['fee'], 0); ?></strong>
                                </div>
                            <?php endif; ?>

                            <a href="<?php echo asset('contact.php'); ?>" class="btn btn-sipway w-100 mt-3">Inquire now</a>
                        </div>

                    </aside>
                </div>

                <!-- Main content -->
                <div class="col-lg-8 order-last order-lg-first">
                    <img class="cd-hero-img"
                         src="<?php echo getImageUrl($class['image'], 'assets/images/placeholder.png'); ?>"
                         alt="<?php echo e($class['title']); ?>">

                    <h2 class="cd-section-title">About this class</h2>
                    <div class="cd-description">
                        <?php echo nl2br(e($class['description'])); ?>
                    </div>

                    <?php if (!empty($schedules)): ?>
                        <h2 class="cd-section-title">Schedule</h2>
                        <div class="cd-schedule">
                            <?php foreach ($schedules as $sch): ?>
                                <div class="cd-sch-row">
                                    <div class="cd-sch-day">
                                        <?php echo e($sch['day_of_week'] ?? formatDate($sch['schedule_date'])); ?>
                                    </div>
                                    <div class="cd-sch-time">
                                        <?php if ($sch['start_time']): ?>
                                            <?php echo date('g:i A', strtotime($sch['start_time'])); ?>
                                            <?php if ($sch['end_time']): ?> – <?php echo date('g:i A', strtotime($sch['end_time'])); endif; ?>
                                        <?php else: ?>Time to be announced<?php endif; ?>
                                        <?php if (!empty($sch['notes'])): ?>
                                            <span class="cd-sch-note"><?php echo e($sch['notes']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>