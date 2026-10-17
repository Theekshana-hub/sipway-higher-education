<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$search    = trim($_GET['q'] ?? '');
$subjectId = (int)($_GET['subject'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;

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

$subjects = $pdo->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$subjectNames = [];
foreach ($subjects as $s) { $subjectNames[(int)$s['id']] = $s['name']; }
if ($subjectId && !isset($subjectNames[$subjectId])) { $subjectId = 0; }

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

$selectTeacher = "NULL AS teacher_name";
$joinTeacher   = "";
if (hasCol($pdo, 'classes', 'teacher_id')) {
    $selectTeacher = "t.full_name AS teacher_name";
    $joinTeacher   = "LEFT JOIN teachers t ON c.teacher_id = t.id";
}

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

$stmt = $pdo->prepare("SELECT c.*, $selectSubject, $selectTeacher
    FROM classes c
    $joinSubject
    $joinTeacher
    WHERE $whereClause
    ORDER BY c.featured DESC, c.created_at DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$classes = $stmt->fetchAll();

$activeSubjectName = $subjectId ? ($subjectNames[$subjectId] ?? '') : '';

$pageTitle = 'Classes';
include __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    .cl-page { --red:#E31E24; --red-dark:#c9181e; --red-soft:#fdecec; --ink:#16181d; --muted:#6b7280; --line:#e9eaee; --bg:#f6f7f9; font-family:'Inter',sans-serif; background:var(--bg); }
    .cl-wrap { max-width:1280px; margin:0 auto; padding:44px 20px 80px; display:grid; grid-template-columns:280px 1fr; gap:32px; align-items:start; }

    .cl-side { background:#fff; border:1px solid var(--line); border-radius:20px; padding:24px; position:sticky; top:90px; }
    .cl-side h5 { font-size:15px; font-weight:700; color:var(--ink); margin:0 0 12px; display:flex; align-items:center; gap:10px; }
    .cl-side h5 i { width:30px; height:30px; border-radius:9px; background:var(--red-soft); color:var(--red); display:inline-flex; align-items:center; justify-content:center; font-size:13px; }
    .cl-side h5.mt { margin-top:26px; padding-top:24px; border-top:1px solid var(--line); }
    .cl-select { width:100%; appearance:none; -webkit-appearance:none; border:1px solid var(--line); border-radius:12px; padding:13px 44px 13px 16px; font-size:15px; font-family:inherit; color:var(--ink); background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23E31E24' stroke-width='3'><polyline points='6 9 12 15 18 9'/></svg>") no-repeat right 16px center; cursor:pointer; outline:none; transition:border-color .2s, box-shadow .2s; }
    .cl-select:focus { border-color:var(--red); box-shadow:0 0 0 4px var(--red-soft); }
    .cl-clear { display:inline-flex; align-items:center; gap:6px; margin-top:14px; color:var(--red); font-size:14px; font-weight:600; text-decoration:none; }
    .cl-clear:hover { text-decoration:underline; }
    .cl-search { display:flex; }
    .cl-search input { flex:1; min-width:0; border:1px solid var(--line); border-right:0; border-radius:12px 0 0 12px; padding:13px 16px; font-size:15px; outline:none; font-family:inherit; color:var(--ink); transition:border-color .2s; }
    .cl-search input:focus { border-color:var(--red); }
    .cl-search button { background:var(--red); color:#fff; border:0; width:52px; border-radius:0 12px 12px 0; cursor:pointer; font-size:15px; transition:background .2s; }
    .cl-search button:hover { background:var(--red-dark); }

    .cl-head { display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:26px; }
    .cl-head h1 { font-size:32px; font-weight:800; color:var(--ink); margin:0; letter-spacing:-.5px; }
    .cl-count { color:var(--muted); font-size:15px; margin-top:4px; }
    .cl-tags { display:flex; gap:8px; flex-wrap:wrap; }
    .cl-tag { display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--line); color:var(--ink); border-radius:999px; padding:8px 14px; font-size:13.5px; font-weight:600; }
    .cl-tag i { color:var(--red); font-size:12px; }

    .cl-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:26px; align-items:start; }
    .cl-card { background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; display:flex; flex-direction:column; transition:transform .2s, box-shadow .2s, border-color .2s; }
    .cl-card:hover { transform:translateY(-4px); box-shadow:0 18px 40px rgba(22,24,29,.10); border-color:transparent; }

    .cl-img { position:relative; aspect-ratio:16/9; background:#1c1e24; overflow:hidden; }
    .cl-img img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .5s; }
    .cl-card:hover .cl-img img { transform:scale(1.05); }
    .cl-img::after { content:""; position:absolute; inset:auto 0 0 0; height:55%; background:linear-gradient(transparent, rgba(0,0,0,.72)); pointer-events:none; }
    .cl-badge { position:absolute; top:14px; left:14px; z-index:2; display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:999px; font-size:12.5px; font-weight:700; color:#fff; background:var(--red); box-shadow:0 6px 14px rgba(227,30,36,.35); }
    .cl-teacher-badge { position:absolute; left:16px; right:16px; bottom:12px; z-index:2; color:#fff; font-size:14px; font-weight:600; display:flex; align-items:center; gap:10px; }
    .cl-teacher-badge .ic { width:30px; height:30px; border-radius:50%; background:rgba(255,255,255,.2); backdrop-filter:blur(6px); display:inline-flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
    .cl-teacher-badge span { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    .cl-body { padding:20px 22px 22px; display:flex; flex-direction:column; flex:1; }
    .cl-cat { display:inline-flex; align-self:flex-start; background:var(--red-soft); color:var(--red); font-size:12.5px; font-weight:700; padding:5px 12px; border-radius:999px; align-items:center; }
    .cl-title { font-size:20px; font-weight:800; color:var(--ink); margin:12px 0 6px; line-height:1.3; letter-spacing:-.2px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .cl-sub-t { color:var(--muted); font-size:14px; font-weight:500; margin-bottom:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .cl-desc { color:#555b66; font-size:15px; line-height:1.6; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

    .cl-meta { display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--line); margin-top:auto; padding-top:16px; }
    .cl-meta-wrap { margin-top:16px; display:flex; flex-direction:column; flex:1; justify-content:flex-end; }
    .cl-dur { color:var(--muted); font-size:14.5px; font-weight:500; display:flex; align-items:center; gap:8px; }
    .cl-dur i { color:var(--red); }
    .cl-price { color:var(--ink); font-size:20px; font-weight:800; }
    .cl-price.free { color:#16a34a; }

.cl-actions .det {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: linear-gradient(135deg, #E31E24, #B91C1C);
    color: #ffffff;
    border: none;
    font-size: 15px;
    font-weight: 700;
    padding: 14px 20px;
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 6px 18px rgba(227, 30, 36, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
}

.cl-actions .det:hover {
    background: linear-gradient(135deg, #B91C1C, #991B1B);
    color: #ffffff;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(227, 30, 36, 0.4);
}

    .cl-actions .det:hover { border-color:var(--red); color:var(--red); }
    .cl-actions .enr { flex:1.4; background:var(--red); color:#fff; text-decoration:none; font-weight:700; text-align:center; padding:12px 14px; border-radius:12px; box-shadow:0 8px 18px rgba(227,30,36,.28); font-size:15px; transition:background .2s, transform .2s; }
    .cl-actions .enr:hover { background:var(--red-dark); transform:translateY(-1px); }
    .cl-actions .enr i { margin-right:6px; }

    .cl-empty { background:#fff; border:1px solid var(--line); border-radius:20px; padding:70px 20px; text-align:center; color:var(--muted); }
    .cl-empty i { font-size:40px; color:var(--red); margin-bottom:16px; }
    .cl-empty h4 { margin:0 0 6px; color:var(--ink); font-size:20px; font-weight:700; }
    .cl-empty p { margin:0; }

    .cl-pager { display:flex; justify-content:center; gap:8px; margin-top:40px; flex-wrap:wrap; }
    .cl-pager a, .cl-pager span { min-width:42px; height:42px; padding:0 12px; display:flex; align-items:center; justify-content:center; border-radius:12px; background:#fff; border:1px solid var(--line); color:#444; text-decoration:none; font-weight:600; transition:border-color .2s, color .2s; }
    .cl-pager a:hover { border-color:var(--red); color:var(--red); }
    .cl-pager .cur { background:var(--red); border-color:var(--red); color:#fff; }
    .cl-pager .dis { opacity:.4; }

    .cl-card a:focus-visible, .cl-pager a:focus-visible, .cl-clear:focus-visible { outline:3px solid var(--red); outline-offset:2px; }

    @media (max-width:1100px) { .cl-grid { grid-template-columns:repeat(2, 1fr); } }
    @media (max-width:860px) { .cl-wrap { grid-template-columns:1fr; } .cl-side { position:static; } }
    @media (max-width:560px) { .cl-grid { grid-template-columns:1fr; } .cl-head h1 { font-size:26px; } }
    @media (prefers-reduced-motion:reduce) { .cl-card, .cl-img img, .cl-actions .enr { transition:none; } .cl-card:hover { transform:none; } .cl-card:hover .cl-img img { transform:none; } }
</style>

<div class="cl-page">
<div class="cl-wrap">
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
            <?php if ($activeSubjectName || $search !== ''): ?>
            <div class="cl-tags">
                <?php if ($activeSubjectName): ?><span class="cl-tag"><i class="fa-solid fa-book"></i> <?php echo e($activeSubjectName); ?></span><?php endif; ?>
                <?php if ($search !== ''): ?><span class="cl-tag"><i class="fa-solid fa-magnifying-glass"></i> <?php echo e($search); ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($classes)): ?>
            <div class="cl-empty">
                <i class="fa-regular fa-folder-open"></i>
                <h4>No classes found</h4>
                <p>Try another subject or search term.</p>
            </div>
        <?php else: ?>
        <div class="cl-grid">
            <?php foreach ($classes as $c):
                $subName = $c['subject_name'] ?? '';
                if ($mode === 'teacher' && $subjectId) { $subName = $subjectNames[$subjectId]; }
                $price = $c['price'] ?? null;
                $teacherName = $c['teacher_name'] ?? '';
            ?>
            <article class="cl-card">
                <div class="cl-img">
                    <img src="<?php echo getImageUrl($c['image']); ?>" alt="<?php echo e($c['title']); ?>" loading="lazy">

                    <?php if (!empty($c['featured'])): ?>
                        <span class="cl-badge"><i class="fa-solid fa-star"></i> Featured</span>
                    <?php endif; ?>

                    <?php if ($teacherName): ?>
                        <div class="cl-teacher-badge">
                            <span class="ic"><i class="fa-solid fa-chalkboard-user"></i></span>
                            <span><?php echo e($teacherName); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="cl-body">
                    <?php if ($subName): ?><div class="cl-cat"><?php echo e($subName); ?></div><?php endif; ?>
                    <h3 class="cl-title"><?php echo e($c['title']); ?></h3>
                    <?php if (!empty($c['subtitle'])): ?><div class="cl-sub-t"><?php echo e($c['subtitle']); ?></div><?php endif; ?>
                    <?php if (!empty($c['description'])): ?><div class="cl-desc"><?php echo e($c['description']); ?></div><?php endif; ?>

                    <div class="cl-meta-wrap">
                        <div class="cl-meta">
                            <span class="cl-dur"><?php if (!empty($c['duration'])): ?><i class="fa-regular fa-clock"></i> <?php echo e($c['duration']); ?><?php endif; ?></span>
                            <?php if ($price !== null): ?>
                                <?php $isFree = !((float)$price > 0); ?>
                                <span class="cl-price <?php echo $isFree ? 'free' : ''; ?>"><?php echo $isFree ? 'Free' : 'Rs. ' . number_format((float)$price); ?></span>
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
            <?php if ($page > 1): ?><a href="<?php echo clUrl(['page' => $page - 1]); ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a><?php else: ?><span class="dis"><i class="fa-solid fa-chevron-left"></i></span><?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?><span class="cur"><?php echo $p; ?></span>
                <?php else: ?><a href="<?php echo clUrl(['page' => $p]); ?>"><?php echo $p; ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?php echo clUrl(['page' => $page + 1]); ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a><?php else: ?><span class="dis"><i class="fa-solid fa-chevron-right"></i></span><?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>