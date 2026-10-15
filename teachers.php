<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$search    = trim($_GET['q'] ?? '');
$subjectId = (int)($_GET['subject'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;

/* ---------- Helpers ---------- */
function hasCol(PDO $pdo, $table, $col) {
    $s = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$s->fetch();
}
function tUrl(array $over = []) {
    global $search, $subjectId;
    $q = array_merge(['q' => $search, 'subject' => $subjectId ?: '', 'page' => ''], $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null && $v !== 0);
    return asset('teachers.php') . ($q ? '?' . http_build_query($q) : '');
}

/* ---------- Subjects (dropdown filter) ---------- */
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$subjectNames = [];
foreach ($subjects as $s) {
    $subjectNames[(int)$s['id']] = $s['name'];
}
if ($subjectId && !isset($subjectNames[$subjectId])) {
    $subjectId = 0;
}

/* ---------- Check relations ---------- */
$hasTeacherIdInClasses = hasCol($pdo, 'classes', 'teacher_id');
$hasTeacherSubjects    = hasCol($pdo, 'teacher_subjects', 'teacher_id')
                      && hasCol($pdo, 'teacher_subjects', 'subject_id');

/* ---------- Teachers query ---------- */
$where  = ["t.status = 'active'"];
$params = [];

if ($search !== '') {
    $where[]  = "(t.full_name LIKE ? OR t.qualification LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Subject filter (via teacher_subjects හෝ classes)
if ($subjectId) {
    if ($hasTeacherSubjects) {
        $where[]  = "t.id IN (SELECT teacher_id FROM teacher_subjects WHERE subject_id = ?)";
        $params[] = $subjectId;
    } elseif ($hasTeacherIdInClasses) {
        // fallback: classes table එකෙන් filter කරන්න (title වගේ)
        $where[]  = "t.id IN (SELECT teacher_id FROM classes WHERE status = 'published' AND title LIKE ?)";
        $params[] = "%" . ($subjectNames[$subjectId] ?? '') . "%";
    }
}

$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM teachers t WHERE $whereClause");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

/* 
   Class Title ගන්න (classes table එකෙන්)
   Teacher කෙනෙක්ට classes කීපයක් තිබුණත් පළවෙනි එක ගන්නවා
*/
$selectClassTitle = "NULL AS class_title";
if ($hasTeacherIdInClasses) {
    $selectClassTitle = "(SELECT c2.title FROM classes c2 
                          WHERE c2.teacher_id = t.id AND c2.status = 'published' 
                          ORDER BY c2.featured DESC, c2.created_at DESC 
                          LIMIT 1) AS class_title";
}

$stmt = $pdo->prepare("SELECT t.*, $selectClassTitle
    FROM teachers t
    WHERE $whereClause
    ORDER BY t.sort_order ASC, t.full_name ASC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Teachers';
include __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    .cl-wrap { --red:#E31E24; --ink:#1a1a1a; font-family:'Inter',sans-serif; max-width:1280px; margin:0 auto; padding:48px 20px 70px; display:grid; grid-template-columns:290px 1fr; gap:30px; align-items:start; }
    .cl-side { background:#fff; border-radius:24px; padding:24px; box-shadow:0 8px 30px rgba(0,0,0,.06); position:sticky; top:90px; }
    .cl-side h5 { font-size:19px; font-weight:700; color:var(--ink); margin:0 0 14px; padding-bottom:14px; border-bottom:2px solid #eee; display:flex; align-items:center; gap:10px; }
    .cl-side h5.mt { margin-top:26px; }
    .cl-select { width:100%; appearance:none; -webkit-appearance:none; border:1px solid #e6e6e6; border-radius:30px; padding:15px 46px 15px 20px; font-size:16px; font-family:inherit; color:#333; background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23E31E24' stroke-width='3'><polyline points='6 9 12 15 18 9'/></svg>") no-repeat right 20px center; cursor:pointer; outline:none; }
    .cl-select:focus { border-color:var(--red); }
    .cl-clear { display:inline-block; margin-top:12px; color:var(--red); font-size:14px; font-weight:600; text-decoration:none; }
    .cl-search { display:flex; }
    .cl-search input { flex:1; min-width:0; border:1px solid #e6e6e6; border-right:0; border-radius:30px 0 0 30px; padding:16px 20px; font-size:16px; outline:none; font-family:inherit; }
    .cl-search button { background:var(--red); color:#fff; border:0; width:62px; border-radius:0 30px 30px 0; cursor:pointer; font-size:16px; }
    .cl-count { color:#666; font-size:19px; margin:2px 0 22px; }
    .cl-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:30px; }
    .cl-card { background:#fff; border-radius:24px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,.07); display:flex; flex-direction:column; transition:transform .2s, box-shadow .2s; }
    .cl-card:hover { transform:translateY(-4px); box-shadow:0 14px 36px rgba(0,0,0,.11); }
    .cl-img { position:relative; aspect-ratio:4/5; background:#111; overflow:hidden; }
    .cl-img img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .4s ease; }
    .cl-card:hover .cl-img img { transform:scale(1.05); }
    .cl-badge { position:absolute; top:16px; right:16px; width:36px; height:36px; border-radius:50%; background:#fff; color:#f5a300; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,.2); font-size:15px; }
    .cl-body { padding:22px 20px 26px; display:flex; flex-direction:column; flex:1; text-align:center; }

    /* Class Title (Biology, Maths...) under image */
    .cl-cat { 
        color:var(--red); 
        font-size:13.5px; 
        font-weight:700; 
        letter-spacing:.8px; 
        text-transform:uppercase; 
        margin-bottom:6px;
        min-height:18px; 
    }

    .cl-title { font-size:22px; font-weight:800; color:var(--ink); margin:0 0 12px; line-height:1.25; }
    .cl-badge-q { display:inline-block; background:#fde8ea; color:var(--red); font-weight:600; font-size:14px; padding:8px 18px; border-radius:50px; margin-bottom:8px; }
    .cl-exp { color:#777; font-size:14.5px; margin-top:6px; }
    .cl-empty { background:#fff; border-radius:24px; padding:60px 20px; text-align:center; color:#777; box-shadow:0 8px 30px rgba(0,0,0,.06); }
    .cl-pager { display:flex; justify-content:center; gap:8px; margin-top:36px; flex-wrap:wrap; }
    .cl-pager a, .cl-pager span { min-width:42px; height:42px; padding:0 12px; display:flex; align-items:center; justify-content:center; border-radius:12px; background:#fff; color:#444; text-decoration:none; font-weight:600; box-shadow:0 2px 8px rgba(0,0,0,.06); }
    .cl-pager .cur { background:var(--red); color:#fff; }
    .cl-pager .dis { opacity:.4; }
    @media (max-width:1100px) { .cl-grid { grid-template-columns:repeat(2, 1fr); } }
    @media (max-width:860px) { .cl-wrap { grid-template-columns:1fr; } .cl-side { position:static; } }
    @media (max-width:560px) { .cl-grid { grid-template-columns:1fr; } }
</style>

<div class="cl-wrap">
    <!-- ===== Sidebar ===== -->
    <aside class="cl-side">
        <h5><i class="fa-solid fa-filter"></i> Subjects</h5>
        <form method="GET" action="<?php echo asset('teachers.php'); ?>" id="subjectForm">
            <?php if ($search !== ''): ?>
                <input type="hidden" name="q" value="<?php echo e($search); ?>">
            <?php endif; ?>
            <select name="subject" class="cl-select" onchange="this.form.submit()">
                <option value="">All Subjects</option>
                <?php foreach ($subjects as $s): ?>
                    <option value="<?php echo (int)$s['id']; ?>" <?php echo $subjectId === (int)$s['id'] ? 'selected' : ''; ?>>
                        <?php echo e($s['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($subjectId || $search !== ''): ?>
            <a class="cl-clear" href="<?php echo asset('teachers.php'); ?>">
                <i class="fa-solid fa-xmark"></i> Clear filters
            </a>
        <?php endif; ?>

        <h5 class="mt"><i class="fa-solid fa-magnifying-glass"></i> Search</h5>
        <form method="GET" action="<?php echo asset('teachers.php'); ?>" class="cl-search">
            <?php if ($subjectId): ?>
                <input type="hidden" name="subject" value="<?php echo $subjectId; ?>">
            <?php endif; ?>
            <input type="text" name="q" placeholder="Search by name..." value="<?php echo e($search); ?>">
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </aside>

    <!-- ===== Teachers grid ===== -->
    <main>
        <div class="cl-count"><?php echo $total; ?> teacher(s) found</div>

        <?php if (empty($teachers)): ?>
            <div class="cl-empty">
                <i class="fa-regular fa-user fa-2x" style="margin-bottom:12px;"></i>
                <p style="margin:0;">No teachers found. Try another subject or search term.</p>
            </div>
        <?php else: ?>
            <div class="cl-grid">
                <?php foreach ($teachers as $t):
                    // Classes page එකේ Title එක
                    $classTitle = $t['class_title'] ?? '';
                ?>
                <article class="cl-card">
                    <!-- Image -->
                    <div class="cl-img">
                        <?php if (!empty($t['profile_image'])): ?>
                            <img src="<?php echo e(getImageUrl($t['profile_image'])); ?>"
                                 alt="<?php echo e($t['full_name']); ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#1c1c1c 0%,#7a0f17 45%,#E31E24 100%);color:rgba(255,255,255,.9);font-size:4.5rem;">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($t['featured'])): ?>
                            <span class="cl-badge"><i class="fa-solid fa-star"></i></span>
                        <?php endif; ?>
                    </div>

                    <!-- Body -->
                    <div class="cl-body">
                        <!-- Class Title (Biology, Maths, Chemistry...) -->
                        <?php if ($classTitle): ?>
                            <div class="cl-cat"><?php echo e($classTitle); ?></div>
                        <?php else: ?>
                            <div class="cl-cat">&nbsp;</div>
                        <?php endif; ?>

                        <!-- Teacher Name -->
                        <h3 class="cl-title"><?php echo e($t['full_name']); ?></h3>

                        <?php if (!empty($t['qualification'])): ?>
                            <span class="cl-badge-q"><?php echo e(truncate($t['qualification'], 40)); ?></span>
                        <?php endif; ?>

                        <?php if ((int)($t['experience_years'] ?? 0) > 0): ?>
                            <div class="cl-exp">
                                <i class="fa-solid fa-award"></i>
                                <?php echo (int)$t['experience_years']; ?> years experience
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav class="cl-pager">
                <?php if ($page > 1): ?>
                    <a href="<?php echo tUrl(['page' => $page - 1]); ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <?php else: ?>
                    <span class="dis"><i class="fa-solid fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="cur"><?php echo $p; ?></span>
                    <?php else: ?>
                        <a href="<?php echo tUrl(['page' => $p]); ?>"><?php echo $p; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo tUrl(['page' => $page + 1]); ?>"><i class="fa-solid fa-chevron-right"></i></a>
                <?php else: ?>
                    <span class="dis"><i class="fa-solid fa-chevron-right"></i></span>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>