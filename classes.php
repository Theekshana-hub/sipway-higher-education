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
function clUrl(array $over = []) {
    global $search, $subjectId;
    $q = array_merge(['q' => $search, 'subject' => $subjectId ?: '', 'page' => ''], $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null && $v !== 0);
    return asset('classes.php') . ($q ? '?' . http_build_query($q) : '');
}

/* ---------- Subjects (dropdown) ---------- */
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$subjectNames = [];
foreach ($subjects as $s) { $subjectNames[(int)$s['id']] = $s['name']; }
if ($subjectId && !isset($subjectNames[$subjectId])) { $subjectId = 0; }

/* ---------- How classes link to subjects ---------- */
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
    $selectSubject = "(SELECT s2.name FROM teacher_subjects ts2 JOIN subjects s2 ON s2.id = ts2.subject_id WHERE ts2.teacher_id = c.teacher_id ORDER BY s2.id LIMIT 1) AS subject_name";
}

/* ---------- Classes query ---------- */
$where  = ["c.status = 'published'"];
$params = [];
if ($search !== '') {
    $where[]  = "(c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
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

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM classes c WHERE $whereClause");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT c.*, $selectSubject
    FROM classes c
    $joinSubject
    WHERE $whereClause
    ORDER BY c.featured DESC, c.created_at DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$classes = $stmt->fetchAll();

$pageTitle = 'Classes';
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
    .cl-img { position:relative; aspect-ratio:4/3; background:#111; }
    .cl-img img { width:100%; height:100%; object-fit:cover; display:block; }
    .cl-badge { position:absolute; top:16px; left:16px; padding:7px 15px; border-radius:20px; font-size:13px; font-weight:600; color:#fff; background:var(--red); }
    .cl-body { padding:22px 26px 26px; display:flex; flex-direction:column; flex:1; }
    .cl-cat { color:var(--red); font-size:13.5px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; min-height:18px; }
    .cl-title { font-size:23px; font-weight:800; color:var(--ink); margin:6px 0 10px; line-height:1.25; }
    .cl-sub-t { color:#777; font-size:14.5px; margin-bottom:6px; }
    .cl-desc { color:#777; font-size:17px; line-height:1.55; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:52px; }
    .cl-meta { display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:auto; padding-top:18px; }
    .cl-dur { color:#666; font-size:16px; display:flex; align-items:center; gap:7px; }
    .cl-price { color:var(--red); font-size:22px; font-weight:800; }
    .cl-actions { display:flex; justify-content:space-between; align-items:center; margin-top:18px; gap:12px; }
    .cl-actions .det { flex:1; text-align:center; color:#555; font-weight:600; text-decoration:none; font-size:16px; }
    .cl-actions .det:hover { color:var(--red); }
    .cl-actions .enr { flex:1.4; background:var(--red); color:#fff; text-decoration:none; font-weight:700; text-align:center; padding:13px 18px; border-radius:30px; box-shadow:0 6px 16px rgba(227,30,36,.3); font-size:16px; }
    .cl-actions .enr:hover { background:#c9181e; }
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
        <form method="GET" action="<?php echo asset('classes.php'); ?>" id="subjectForm">
            <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?php echo e($search); ?>"><?php endif; ?>
            <select name="subject" class="cl-select" onchange="this.form.submit()">
                <option value="">All Subjects</option>
                <?php foreach ($subjects as $s): ?>
                    <option value="<?php echo (int)$s['id']; ?>" <?php echo $subjectId === (int)$s['id'] ? 'selected' : ''; ?>><?php echo e($s['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($subjectId || $search !== ''): ?>
            <a class="cl-clear" href="<?php echo asset('classes.php'); ?>"><i class="fa-solid fa-xmark"></i> Clear filters</a>
        <?php endif; ?>

        <h5 class="mt"><i class="fa-solid fa-magnifying-glass"></i> Search</h5>
        <form method="GET" action="<?php echo asset('classes.php'); ?>" class="cl-search">
            <?php if ($subjectId): ?><input type="hidden" name="subject" value="<?php echo $subjectId; ?>"><?php endif; ?>
            <input type="text" name="q" placeholder="Search..." value="<?php echo e($search); ?>">
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </aside>

    <!-- ===== Classes grid ===== -->
    <main>
        <div class="cl-count"><?php echo $total; ?> class(es) found</div>

        <?php if (empty($classes)): ?>
            <div class="cl-empty"><i class="fa-regular fa-folder-open fa-2x mb-3"></i><p class="mb-0">No classes found. Try another subject or search term.</p></div>
        <?php else: ?>
        <div class="cl-grid">
            <?php foreach ($classes as $c):
                $subName = $c['subject_name'] ?? '';
                if ($mode === 'teacher' && $subjectId) { $subName = $subjectNames[$subjectId]; }
                $price = $c['price'] ?? null;
            ?>
            <article class="cl-card">
                <div class="cl-img">
                    <img src="<?php echo getImageUrl($c['image']); ?>" alt="<?php echo e($c['title']); ?>" loading="lazy">
                    <?php if (!empty($c['featured'])): ?><span class="cl-badge">Featured</span><?php endif; ?>
                </div>
                <div class="cl-body">
                    <div class="cl-cat"><?php echo e($subName); ?></div>
                    <h3 class="cl-title"><?php echo e($c['title']); ?></h3>
                    <?php if (!empty($c['subtitle'])): ?><div class="cl-sub-t"><?php echo e($c['subtitle']); ?></div><?php endif; ?>
                    <div class="cl-desc"><?php echo e($c['description'] ?? ''); ?></div>
                    <div class="cl-meta">
                        <span class="cl-dur"><?php if (!empty($c['duration'])): ?><i class="fa-regular fa-clock"></i> <?php echo e($c['duration']); ?><?php endif; ?></span>
                        <?php if ($price !== null): ?>
                            <span class="cl-price"><?php echo ((float)$price > 0) ? 'Rs. ' . number_format((float)$price) : 'Free'; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cl-actions">
                        <a class="det" href="<?php echo asset('class-details.php?id=' . (int)$c['id']); ?>">Details</a>
                        <a class="enr" href="<?php echo asset('enroll.php?class_id=' . (int)$c['id']); ?>"><i class="fa-solid fa-credit-card"></i> Enroll Now</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="cl-pager">
            <?php if ($page > 1): ?><a href="<?php echo clUrl(['page' => $page - 1]); ?>"><i class="fa-solid fa-chevron-left"></i></a><?php else: ?><span class="dis"><i class="fa-solid fa-chevron-left"></i></span><?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?><span class="cur"><?php echo $p; ?></span>
                <?php else: ?><a href="<?php echo clUrl(['page' => $p]); ?>"><?php echo $p; ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?php echo clUrl(['page' => $page + 1]); ?>"><i class="fa-solid fa-chevron-right"></i></a><?php else: ?><span class="dis"><i class="fa-solid fa-chevron-right"></i></span><?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>