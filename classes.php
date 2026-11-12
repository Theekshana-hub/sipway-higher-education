<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$search     = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$subjectId  = (int)($_GET['subject'] ?? 0);
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 12;

function hasCol(PDO $pdo, $table, $col) {
    $s = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$s->fetch();
}

function clUrl(array $over = []) {
    global $search, $categoryId, $subjectId;
    $q = array_merge([
        'q'        => $search,
        'category' => $categoryId ?: '',
        'subject'  => $subjectId ?: '',
        'page'     => ''
    ], $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null && $v !== 0);
    return asset('classes.php') . ($q ? '?' . http_build_query($q) : '');
}

function clCatIcon($name) {
    $n = strtolower($name);
    if (strpos($n, '1 to 5') !== false || strpos($n, 'grade 1') !== false) return 'fa-pencil';
    if (strpos($n, '6 to 9') !== false)  return 'fa-user-graduate';
    if (strpos($n, 'a/l') !== false || strpos($n, 'advanced') !== false) return 'fa-building-columns';
    if (strpos($n, 'o/l') !== false || strpos($n, 'ordinary') !== false) return 'fa-book-open';
    if (strpos($n, 'language') !== false) return 'fa-id-card';
    if (strpos($n, 'scholarship') !== false) return 'fa-award';
    return 'fa-folder';
}

/* ---------- Load Categories (with hierarchy) ---------- */
$allCategories = $pdo->query("
    SELECT id, name, parent_id, sort_order 
    FROM categories 
    WHERE status = 'active' 
    ORDER BY sort_order, name
")->fetchAll();

$topCategories = [];
$subCategories = [];
foreach ($allCategories as $cat) {
    if (empty($cat['parent_id'])) {
        $topCategories[] = $cat;
    } else {
        $subCategories[$cat['parent_id']][] = $cat;
    }
}

/* ---------- Load Subjects ---------- */
$subjects = $pdo->query("SELECT id, name, category_id FROM subjects WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$subjectNames = [];
$subjectsByCategory = [];
foreach ($subjects as $s) {
    $subjectNames[(int)$s['id']] = $s['name'];
    if ($s['category_id']) {
        $subjectsByCategory[$s['category_id']][] = $s;
    }
}

// Validate selected category / subject
if ($categoryId) {
    $validCat = false;
    foreach ($allCategories as $c) {
        if ((int)$c['id'] === $categoryId) {
            $validCat = true;
            break;
        }
    }
    if (!$validCat) $categoryId = 0;
}
if ($subjectId && !isset($subjectNames[$subjectId])) {
    $subjectId = 0;
}

/* ---------- Detect relation mode ---------- */
$mode = 'none';
if (hasCol($pdo, 'classes', 'subject_id')) {
    $mode = 'direct';
} elseif (hasCol($pdo, 'classes', 'teacher_id')
    && hasCol($pdo, 'teacher_subjects', 'teacher_id')
    && hasCol($pdo, 'teacher_subjects', 'subject_id')) {
    $mode = 'teacher';
}

$selectSubject = "NULL AS subject_name";
$joinSubject   = "";
if ($mode === 'direct') {
    $selectSubject = "s.name AS subject_name";
    $joinSubject   = "LEFT JOIN subjects s ON c.subject_id = s.id";
} elseif ($mode === 'teacher') {
    $selectSubject = "(SELECT s2.name FROM teacher_subjects ts2 
                       JOIN subjects s2 ON s2.id = ts2.subject_id 
                       WHERE ts2.teacher_id = c.teacher_id 
                       ORDER BY s2.id LIMIT 1) AS subject_name";
}

$selectTeacher = "NULL AS teacher_name, NULL AS teacher_image";
$joinTeacher   = "";
if (hasCol($pdo, 'classes', 'teacher_id')) {
    $selectTeacher = "t.full_name AS teacher_name";
    $joinTeacher   = "LEFT JOIN teachers t ON c.teacher_id = t.id";

    if (hasCol($pdo, 'teachers', 'image')) {
        $selectTeacher .= ", t.image AS teacher_image";
    } elseif (hasCol($pdo, 'teachers', 'photo')) {
        $selectTeacher .= ", t.photo AS teacher_image";
    } elseif (hasCol($pdo, 'teachers', 'avatar')) {
        $selectTeacher .= ", t.avatar AS teacher_image";
    } elseif (hasCol($pdo, 'teachers', 'profile_image')) {
        $selectTeacher .= ", t.profile_image AS teacher_image";
    } else {
        $selectTeacher .= ", NULL AS teacher_image";
    }
}

/* ---------- Build WHERE ---------- */
$where  = ["c.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[]  = "(c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Category filter (includes sub-categories)
if ($categoryId) {
    $catIds = [$categoryId];
    if (isset($subCategories[$categoryId])) {
        foreach ($subCategories[$categoryId] as $sub) {
            $catIds[] = (int)$sub['id'];
        }
    }

    $subjectIdsUnderCat = [];
    foreach ($subjects as $s) {
        if (in_array((int)$s['category_id'], $catIds)) {
            $subjectIdsUnderCat[] = (int)$s['id'];
        }
    }

    if (!empty($subjectIdsUnderCat)) {
        if ($mode === 'direct') {
            $placeholders = implode(',', array_fill(0, count($subjectIdsUnderCat), '?'));
            $where[] = "c.subject_id IN ($placeholders)";
            $params = array_merge($params, $subjectIdsUnderCat);
        } elseif ($mode === 'teacher') {
            $placeholders = implode(',', array_fill(0, count($subjectIdsUnderCat), '?'));
            $where[] = "c.teacher_id IN (
                SELECT teacher_id FROM teacher_subjects WHERE subject_id IN ($placeholders)
            )";
            $params = array_merge($params, $subjectIdsUnderCat);
        }
    } else {
        $where[] = "1 = 0";
    }
}

// Subject filter (more specific)
if ($subjectId) {
    if ($mode === 'direct') {
        $where[]  = "c.subject_id = ?";
        $params[] = $subjectId;
    } elseif ($mode === 'teacher') {
        $where[]  = "c.teacher_id IN (SELECT teacher_id FROM teacher_subjects WHERE subject_id = ?)";
        $params[] = $subjectId;
    }
}

$whereClause = implode(' AND ', $where);

/* ---------- Count + Fetch ---------- */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM classes c WHERE $whereClause");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT c.*, $selectSubject, $selectTeacher
    FROM classes c
    $joinSubject
    $joinTeacher
    WHERE $whereClause
    ORDER BY c.featured DESC, c.created_at DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$classes = $stmt->fetchAll();

/* ---------- Load schedules (Year / Day / Time / Fee) for these classes ---------- */
$schedulesByClass = [];
if (!empty($classes)) {
    try {
        $ids = array_map(fn($r) => (int)$r['id'], $classes);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $ss  = $pdo->prepare("SELECT * FROM class_year_schedules WHERE class_id IN ($ph) ORDER BY sort_order, id");
        $ss->execute($ids);
        foreach ($ss->fetchAll() as $row) {
            $schedulesByClass[(int)$row['class_id']][] = $row;
        }
    } catch (Exception $ex) {
        $schedulesByClass = [];
    }
}

function clTime($t) {
    return $t ? date('g:i A', strtotime($t)) : '';
}

/* ---------- Active labels ---------- */
$activeCategoryName = '';
$activeSubjectName  = $subjectId ? ($subjectNames[$subjectId] ?? '') : '';

if ($categoryId) {
    foreach ($allCategories as $c) {
        if ((int)$c['id'] === $categoryId) {
            $activeCategoryName = $c['name'];
            break;
        }
    }
}

$pageTitle = 'Classes';
include __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<style>
    .cl-page { --red:#E31E24; --red-dark:#c9181e; --red-soft:#fdecec; --ink:#16181d; --muted:#6b7280; --line:#e9eaee; --bg:#f6f7f9; font-family:'Inter',sans-serif; background:var(--bg); }
    .cl-wrap { max-width:1280px; margin:0 auto; padding:44px 20px 80px; display:grid; grid-template-columns:290px minmax(0,1fr); gap:32px; align-items:start; }
    .cl-wrap > main { min-width:0; }

    /* ---------- Sidebar ---------- */
    .cl-side { background:#fff; border:1px solid var(--line); border-radius:28px; padding:26px 20px; position:sticky; top:90px; box-shadow:0 10px 30px rgba(22,24,29,.05); }
    .cl-side h5 { font-family:'Poppins','Inter',sans-serif; font-size:17px; font-weight:700; color:var(--ink); margin:0 0 14px; padding-bottom:12px; border-bottom:2px solid var(--line); display:flex; align-items:center; gap:10px; }
    .cl-side h5 i { font-size:16px; color:var(--ink); }
    .cl-side h5.mt { margin-top:26px; }

    /* ---------- Category list ---------- */
    .cl-cat-list { list-style:none; padding:0; margin:0 0 6px; }
    .cl-cat-list > li { margin-bottom:4px; }

    .cl-cat-row { display:flex; align-items:center; border-radius:12px; transition:background .15s, color .15s; }
    .cl-cat-row:hover { background:var(--red-soft); }
    .cl-cat-row.active { background:var(--red-soft); }

    .cl-cat-link {
        display:flex; align-items:center; gap:12px;
        padding:13px 14px; border-radius:12px;
        color:var(--ink); text-decoration:none; font-size:15px; font-weight:500;
        flex:0 1 auto; min-width:0;
    }
    .cl-cat-link i { width:18px; text-align:center; font-size:14px; flex-shrink:0; }
    .cl-cat-row:hover .cl-cat-link { color:var(--red); }
    .cl-cat-row.active .cl-cat-link { color:var(--red); font-weight:600; }
    .cl-cat-row.no-sub { }
    .cl-cat-row.no-sub .cl-cat-link { flex:1; }

    .cl-chev-btn {
        flex:1; align-self:stretch; display:flex; align-items:center; justify-content:flex-start;
        background:transparent; border:0; cursor:pointer; color:var(--ink);
        padding:0 14px 0 2px; font-size:12px; border-radius:0 12px 12px 0;
    }
    .cl-cat-row:hover .cl-chev-btn, .cl-cat-row.active .cl-chev-btn { color:var(--red); }
    .cl-chev-btn i { transition:transform .25s; }
    .cl-chev-btn[aria-expanded="true"] i { transform:rotate(180deg); }
    .cl-chev-btn:focus-visible, .cl-cat-link:focus-visible, .cl-sub a:focus-visible { outline:3px solid var(--red); outline-offset:2px; }

    /* ---------- Sub-category dropdown ---------- */
    .cl-sub { list-style:none; margin:4px 0 6px; padding:0; display:none; }
    .cl-sub.open { display:block; animation:clDrop .2s ease; }
    @keyframes clDrop { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
    .cl-sub li { margin-bottom:2px; }
    .cl-sub a {
        display:flex; align-items:center; gap:12px;
        padding:12px 14px 12px 22px; border-radius:10px;
        color:var(--ink); text-decoration:none; font-size:15px; font-weight:500;
        transition:background .15s, color .15s;
    }
    .cl-sub a i { font-size:11px; width:12px; text-align:center; }
    .cl-sub a:hover { background:var(--red-soft); color:var(--red); }
    .cl-sub a.active { color:var(--red); font-weight:700; background:var(--red-soft); }

    .cl-clear { display:inline-flex; align-items:center; gap:6px; margin:10px 0 0 6px; color:var(--red); font-size:14px; font-weight:600; text-decoration:none; }
    .cl-clear:hover { text-decoration:underline; }

    /* ---------- Search ---------- */
    .cl-search { display:flex; }
    .cl-search input { flex:1; min-width:0; border:1px solid var(--line); border-right:0; border-radius:12px 0 0 12px; padding:13px 16px; font-size:15px; outline:none; font-family:inherit; color:var(--ink); transition:border-color .2s; }
    .cl-search input:focus { border-color:var(--red); }
    .cl-search button { background:var(--red); color:#fff; border:0; width:52px; border-radius:0 12px 12px 0; cursor:pointer; font-size:15px; transition:background .2s; }
    .cl-search button:hover { background:var(--red-dark); }

    /* ---------- Header ---------- */
    .cl-head { display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:26px; }
    .cl-head h1 { font-size:32px; font-weight:800; color:var(--ink); margin:0; letter-spacing:-.5px; }
    .cl-count { color:var(--muted); font-size:15px; margin-top:4px; }
    .cl-tags { display:flex; gap:8px; flex-wrap:wrap; }
    .cl-tag { display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--line); color:var(--ink); border-radius:999px; padding:8px 14px; font-size:13.5px; font-weight:600; }
    .cl-tag i { color:var(--red); font-size:12px; }

    /* ---------- GRID ---------- */
    .cl-grid {
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:24px;
        align-items:stretch;
    }
    .cl-card {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
        display:flex; flex-direction:column; height:100%; min-width:0;
        transition:transform .2s, box-shadow .2s, border-color .2s;
    }
    .cl-card:hover { transform:translateY(-4px); box-shadow:0 16px 36px rgba(22,24,29,.10); border-color:transparent; }

    .cl-img { position:relative; aspect-ratio:16/9; background:#1c1e24; overflow:hidden; flex-shrink:0; }
    .cl-img img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .5s; }
    .cl-card:hover .cl-img img { transform:scale(1.05); }
    .cl-img::after { content:""; position:absolute; inset:auto 0 0 0; height:55%; background:linear-gradient(transparent, rgba(0,0,0,.72)); pointer-events:none; }
    .cl-badge { position:absolute; top:12px; left:12px; z-index:2; display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:999px; font-size:12px; font-weight:700; color:#fff; background:var(--red); box-shadow:0 5px 12px rgba(227,30,36,.35); }

    .cl-body { padding:16px 18px 18px; display:flex; flex-direction:column; flex:1; min-width:0; }

    .cl-cat-slot { min-height:26px; margin-bottom:10px; display:flex; align-items:flex-start; }
    .cl-cat { display:inline-flex; align-self:flex-start; background:var(--red-soft); color:var(--red); font-size:12px; font-weight:700; padding:4px 10px; border-radius:999px; align-items:center; max-width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    .cl-teacher { display:flex; align-items:center; gap:10px; margin-bottom:8px; min-height:40px; }
    .cl-teacher-img { width:38px; height:38px; border-radius:50%; object-fit:cover; flex-shrink:0; border:2px solid #e5e7eb; background:#f3f4f6; }
    .cl-teacher-fallback { width:38px; height:38px; border-radius:50%; background:var(--red-soft); color:var(--red); display:inline-flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; border:2px solid #fdecec; }
    .cl-teacher-name { font-size:13.5px; font-weight:600; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }

    .cl-title { font-size:18px; font-weight:800; color:var(--ink); margin:0 0 4px; line-height:1.3; letter-spacing:-.2px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.5em; }
    .cl-sub-t { color:var(--muted); font-size:13px; font-weight:500; margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-height:18px; }
    .cl-desc { color:#555b66; font-size:13.5px; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.9em; }

    /* ---------- Compact Schedule ---------- */
    .cl-schedule { display:flex; flex-direction:column; gap:6px; margin:12px 0 2px; }
    .cl-slot {
        display:flex; flex-wrap:wrap; align-items:center; gap:5px;
        background:#f6f7f9; border:1px solid var(--line); border-radius:10px;
        padding:6px 8px;
    }
    .cl-chip {
        display:inline-flex; align-items:center; gap:4px;
        background:#fff; color:var(--ink); border-radius:7px;
        padding:3px 7px; font-size:11.5px; font-weight:600;
        border:1px solid var(--line); line-height:1.3;
    }
    .cl-chip i { color:var(--red); font-size:10px; }
    .cl-chip.year { background:var(--red); color:#fff; border-color:var(--red); }
    .cl-chip.year i { color:#fff; }
    .cl-chip.fee { background:#ecfdf3; color:#15803d; border-color:#bbf7d0; margin-left:auto; font-weight:700; }
    .cl-chip.fee i { color:#15803d; }

    .cl-more-slots {
        font-size:12px; font-weight:600; color:var(--red);
        padding:2px 4px; margin-top:2px;
    }

    /* Footer */
    .cl-meta-wrap { margin-top:auto; padding-top:14px; display:flex; flex-direction:column; }
    .cl-meta { display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); padding-top:14px; min-height:48px; }
    .cl-dur { color:var(--muted); font-size:13.5px; font-weight:500; display:flex; align-items:center; gap:6px; }
    .cl-dur i { color:var(--red); }
    .cl-price { color:var(--ink); font-size:18px; font-weight:800; }
    .cl-price.free { color:#16a34a; }

    .cl-actions { margin-top:12px; display:flex; }
    .cl-actions .det {
        flex:1; display:inline-flex; align-items:center; justify-content:center; gap:7px; text-align:center;
        background:linear-gradient(135deg, #E31E24, #B91C1C); color:#ffffff; border:none;
        font-size:14.5px; font-weight:700; padding:12px 16px; border-radius:11px; text-decoration:none;
        box-shadow:0 5px 15px rgba(227,30,36,.28); transition:all .25s ease; cursor:pointer;
    }
    .cl-actions .det:hover {
        background:linear-gradient(135deg, #B91C1C, #991B1B); color:#ffffff;
        transform:translateY(-2px); box-shadow:0 8px 20px rgba(227,30,36,.38);
    }

    .cl-empty { background:#fff; border:1px solid var(--line); border-radius:18px; padding:60px 20px; text-align:center; color:var(--muted); }
    .cl-empty i { font-size:38px; color:var(--red); margin-bottom:14px; }
    .cl-empty h4 { margin:0 0 6px; color:var(--ink); font-size:19px; font-weight:700; }
    .cl-empty p { margin:0; }

    .cl-pager { display:flex; justify-content:center; gap:8px; margin-top:36px; flex-wrap:wrap; }
    .cl-pager a, .cl-pager span { min-width:40px; height:40px; padding:0 11px; display:flex; align-items:center; justify-content:center; border-radius:11px; background:#fff; border:1px solid var(--line); color:#444; text-decoration:none; font-weight:600; transition:border-color .2s, color .2s; }
    .cl-pager a:hover { border-color:var(--red); color:var(--red); }
    .cl-pager .cur { background:var(--red); border-color:var(--red); color:#fff; }
    .cl-pager .dis { opacity:.4; }

    .cl-card a:focus-visible, .cl-pager a:focus-visible, .cl-clear:focus-visible { outline:3px solid var(--red); outline-offset:2px; }

    @media (max-width:1100px) { .cl-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (max-width:860px) { .cl-wrap { grid-template-columns:minmax(0,1fr); } .cl-side { position:static; } }
    @media (max-width:560px) { .cl-grid { grid-template-columns:minmax(0,1fr); } .cl-head h1 { font-size:26px; } }
    @media (prefers-reduced-motion: reduce) {
        .cl-card, .cl-img img, .cl-actions .det, .cl-chev-btn i { transition:none; }
        .cl-sub.open { animation:none; }
        .cl-card:hover { transform:none; }
        .cl-card:hover .cl-img img { transform:none; }
    }
</style>

<div class="cl-page">
<div class="cl-wrap">
    <aside class="cl-side">
        <!-- Categories -->
        <h5><i class="fa-solid fa-filter"></i> Categories</h5>
        <ul class="cl-cat-list">

            <!-- All Classes -->
            <li>
                <div class="cl-cat-row no-sub <?php echo !$categoryId ? 'active' : ''; ?>">
                    <a class="cl-cat-link" href="<?php echo clUrl(['category' => '', 'subject' => '', 'page' => 1]); ?>">
                        <i class="fa-solid fa-table-cells"></i> All Classes
                    </a>
                </div>
            </li>

            <?php foreach ($topCategories as $cat):
                $catId    = (int)$cat['id'];
                $subs     = $subCategories[$catId] ?? [];
                $hasSubs  = !empty($subs);
                $subIds   = array_map(fn($x) => (int)$x['id'], $subs);
                $isActive = ($categoryId === $catId) || in_array($categoryId, $subIds, true);
                $subListId = 'cl-sub-' . $catId;
            ?>
            <li>
                <div class="cl-cat-row <?php echo $isActive ? 'active' : ''; ?> <?php echo $hasSubs ? '' : 'no-sub'; ?>">
                    <a class="cl-cat-link" href="<?php echo clUrl(['category' => $catId, 'subject' => '', 'page' => 1]); ?>">
                        <i class="fa-solid <?php echo clCatIcon($cat['name']); ?>"></i>
                        <?php echo e($cat['name']); ?>
                    </a>
                    <?php if ($hasSubs): ?>
                        <button type="button" class="cl-chev-btn"
                                aria-expanded="<?php echo $isActive ? 'true' : 'false'; ?>"
                                aria-controls="<?php echo $subListId; ?>"
                                aria-label="Toggle <?php echo e($cat['name']); ?>">
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($hasSubs): ?>
                    <ul class="cl-sub <?php echo $isActive ? 'open' : ''; ?>" id="<?php echo $subListId; ?>">
                        <?php foreach ($subs as $sub): ?>
                            <li>
                                <a href="<?php echo clUrl(['category' => (int)$sub['id'], 'subject' => '', 'page' => 1]); ?>"
                                   class="<?php echo $categoryId === (int)$sub['id'] ? 'active' : ''; ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                    <?php echo e($sub['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($categoryId || $subjectId || $search !== ''): ?>
            <a class="cl-clear" href="<?php echo asset('classes.php'); ?>">
                <i class="fa-solid fa-xmark"></i> Clear filters
            </a>
        <?php endif; ?>

        <!-- Search -->
        <h5 class="mt"><i class="fa-solid fa-magnifying-glass"></i> Search</h5>
        <form method="GET" action="<?php echo asset('classes.php'); ?>" class="cl-search">
            <?php if ($categoryId): ?><input type="hidden" name="category" value="<?php echo $categoryId; ?>"><?php endif; ?>
            <?php if ($subjectId): ?><input type="hidden" name="subject" value="<?php echo $subjectId; ?>"><?php endif; ?>
            <input type="text" name="q" placeholder="Search classes..." value="<?php echo e($search); ?>">
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </aside>

    <main>
        <div class="cl-head">
            <div>
                <h1>Classes</h1>
                <div class="cl-count"><?php echo $total; ?> class(es) found</div>
            </div>
            <?php if ($activeCategoryName || $activeSubjectName || $search !== ''): ?>
            <div class="cl-tags">
                <?php if ($activeCategoryName): ?>
                    <span class="cl-tag"><i class="fa-solid fa-folder"></i> <?php echo e($activeCategoryName); ?></span>
                <?php endif; ?>
                <?php if ($activeSubjectName): ?>
                    <span class="cl-tag"><i class="fa-solid fa-book"></i> <?php echo e($activeSubjectName); ?></span>
                <?php endif; ?>
                <?php if ($search !== ''): ?>
                    <span class="cl-tag"><i class="fa-solid fa-magnifying-glass"></i> <?php echo e($search); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($classes)): ?>
            <div class="cl-empty">
                <i class="fa-regular fa-folder-open"></i>
                <h4>No classes found</h4>
                <p>Try another category or search term.</p>
            </div>
        <?php else: ?>
        <div class="cl-grid">
            <?php foreach ($classes as $c):
                $subName = $c['subject_name'] ?? '';
                if ($mode === 'teacher' && $subjectId) { $subName = $subjectNames[$subjectId]; }
                $price = $c['price'] ?? null;
                $teacherName = $c['teacher_name'] ?? '';
                $teacherImage = $c['teacher_image'] ?? null;
                $classSchedules = $schedulesByClass[(int)$c['id']] ?? [];
                $hasSlotFee = false;
                foreach ($classSchedules as $scChk) {
                    if ($scChk['fee'] !== null && $scChk['fee'] !== '') { $hasSlotFee = true; break; }
                }

                // Show max 3 slots on the card
                $maxSlots = 3;
                $visibleSchedules = array_slice($classSchedules, 0, $maxSlots);
                $extraCount = count($classSchedules) - count($visibleSchedules);
            ?>
            <article class="cl-card">
                <div class="cl-img">
                    <img src="<?php echo getImageUrl($c['image']); ?>" alt="<?php echo e($c['title']); ?>" loading="lazy">
                    <?php if (!empty($c['featured'])): ?>
                        <span class="cl-badge"><i class="fa-solid fa-star"></i> Featured</span>
                    <?php endif; ?>
                </div>

                <div class="cl-body">
                    <div class="cl-cat-slot">
                        <?php if ($subName): ?>
                            <div class="cl-cat"><?php echo e($subName); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="cl-teacher">
                        <?php if ($teacherName): ?>
                            <?php if (!empty($teacherImage)): ?>
                                <img src="<?php echo getImageUrl($teacherImage); ?>" 
                                     alt="<?php echo e($teacherName); ?>" 
                                     class="cl-teacher-img" loading="lazy">
                            <?php else: ?>
                                <span class="cl-teacher-fallback">
                                    <i class="fa-solid fa-chalkboard-user"></i>
                                </span>
                            <?php endif; ?>
                            <span class="cl-teacher-name"><?php echo e($teacherName); ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="cl-title"><?php echo e($c['title']); ?></h3>

                    <div class="cl-sub-t"><?php echo !empty($c['subtitle']) ? e($c['subtitle']) : ''; ?></div>

                    <div class="cl-desc"><?php echo !empty($c['description']) ? e($c['description']) : ''; ?></div>

                    <?php if (!empty($visibleSchedules)): ?>
                        <div class="cl-schedule">
                            <?php foreach ($visibleSchedules as $sc):
                                $t = clTime($sc['start_time']);
                                if ($t && !empty($sc['end_time'])) { $t .= ' – ' . clTime($sc['end_time']); }
                                $slotFee = ($sc['fee'] !== null && $sc['fee'] !== '') ? (float)$sc['fee'] : null;
                            ?>
                                <div class="cl-slot">
                                    <?php if (!empty($sc['class_year'])): ?>
                                        <span class="cl-chip year"><i class="fa-solid fa-graduation-cap"></i> <?php echo e($sc['class_year']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($sc['class_day'])): ?>
                                        <span class="cl-chip"><i class="fa-regular fa-calendar"></i> <?php echo e($sc['class_day']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($t): ?>
                                        <span class="cl-chip"><i class="fa-regular fa-clock"></i> <?php echo e($t); ?></span>
                                    <?php endif; ?>
                                    <?php if ($slotFee !== null): ?>
                                        <span class="cl-chip fee"><i class="fa-solid fa-tag"></i> <?php echo $slotFee > 0 ? 'Rs. ' . number_format($slotFee) : 'Free'; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($extraCount > 0): ?>
                                <div class="cl-more-slots">+<?php echo $extraCount; ?> more schedule<?php echo $extraCount > 1 ? 's' : ''; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="cl-meta-wrap">
                        <div class="cl-meta">
                            <span class="cl-dur">
                                <?php if (!empty($c['duration'])): ?>
                                    <i class="fa-regular fa-clock"></i> <?php echo e($c['duration']); ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($price !== null && !$hasSlotFee): ?>
                                <?php $isFree = !((float)$price > 0); ?>
                                <span class="cl-price <?php echo $isFree ? 'free' : ''; ?>">
                                    <?php echo $isFree ? 'Free' : 'Rs. ' . number_format((float)$price); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="cl-actions">
                            <a class="det" href="<?php echo asset('class-details.php?id=' . (int)$c['id']); ?>">
                                View Details <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="cl-pager" aria-label="Pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo clUrl(['page' => $page - 1]); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a>
            <?php else: ?>
                <span class="dis"><i class="fa-solid fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="cur"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="<?php echo clUrl(['page' => $p]); ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?php echo clUrl(['page' => $page + 1]); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a>
            <?php else: ?>
                <span class="dis"><i class="fa-solid fa-chevron-right"></i></span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
</div>

<script>
(function () {
    document.querySelectorAll('.cl-chev-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var list = document.getElementById(btn.getAttribute('aria-controls'));
            if (!list) return;
            var open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            list.classList.toggle('open', !open);
        });
    });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>