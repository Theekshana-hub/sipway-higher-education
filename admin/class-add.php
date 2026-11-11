<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$error = '';

function addCsrfOk() {
    $html = (string)csrfField();
    if (!preg_match('/name=["\']([^"\']+)["\']/', $html, $n) || !preg_match('/value=["\']([^"\']*)["\']/', $html, $v)) {
        return true;
    }
    $posted = $_POST[$n[1]] ?? '';
    return is_string($posted) && hash_equals($v[1], $posted);
}

function addHasCol(PDO $pdo, $table, $col) {
    $s = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
    return (bool)$s->fetch();
}

/* ---------- Handle submit ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    if (!addCsrfOk()) {
        $error = 'Invalid request token. Page eka refresh karala ayeth try karanna.';
    } else {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $error = 'Title eka one.';
        } else {
            $status = in_array($_POST['status'] ?? '', ['draft','published','archived'], true) ? $_POST['status'] : 'draft';
            $type   = in_array($_POST['class_type'] ?? '', ['physical','online','hybrid'], true) ? $_POST['class_type'] : 'physical';
            $catId  = (int)($_POST['category_id'] ?? 0) ?: null;
            $subId  = (int)($_POST['subject_id'] ?? 0) ?: null;
            $teaId  = (int)($_POST['teacher_id'] ?? 0) ?: null;
            $fee    = ($_POST['fee'] ?? '') !== '' && is_numeric($_POST['fee']) ? round((float)$_POST['fee'], 2) : 0;
            $startD = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $feat   = !empty($_POST['featured']) ? 1 : 0;

            $cols = ['title','status','short_description','description','category_id','subject_id','teacher_id','class_type','location','duration','fee','start_date','featured'];
            $vals = [
                $title, $status,
                trim($_POST['short_description'] ?? ''),
                trim($_POST['description'] ?? ''),
                $catId, $subId, $teaId, $type,
                trim($_POST['location'] ?? ''),
                trim($_POST['duration'] ?? ''),
                $fee, $startD, $feat
            ];

            // slug column thiyenawanam auto hadanawa
            if (addHasCol($pdo, 'classes', 'slug')) {
                $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
                if ($slug === '') $slug = 'class';
                $slug .= '-' . substr(md5(uniqid('', true)), 0, 6);
                $cols[] = 'slug';
                $vals[] = $slug;
            }

            $years  = $_POST['sch_year']  ?? [];
            $dayArr = $_POST['sch_day']   ?? [];
            $starts = $_POST['sch_start'] ?? [];
            $ends   = $_POST['sch_end']   ?? [];
            $fees   = $_POST['sch_fee']   ?? [];

            $newId = 0;
            try {
                $pdo->beginTransaction();
                $ph = implode(',', array_fill(0, count($cols), '?'));
                $pdo->prepare("INSERT INTO classes (`" . implode('`,`', $cols) . "`) VALUES ($ph)")->execute($vals);
                $newId = (int)$pdo->lastInsertId();

                $ins = $pdo->prepare("INSERT INTO class_year_schedules (class_id, class_year, class_day, start_time, end_time, fee, sort_order) VALUES (?,?,?,?,?,?,?)");
                $order = 0;
                foreach ($years as $i => $y) {
                    $y  = mb_substr(trim($y), 0, 30);
                    $d  = $dayArr[$i] ?? '';
                    $d  = in_array($d, $days, true) ? $d : null;
                    $st = $starts[$i] ?? '';
                    $st = preg_match('/^\d{2}:\d{2}$/', $st) ? $st . ':00' : null;
                    $en = $ends[$i] ?? '';
                    $en = preg_match('/^\d{2}:\d{2}$/', $en) ? $en . ':00' : null;
                    $fe = trim((string)($fees[$i] ?? ''));
                    $fe = (is_numeric($fe) && (float)$fe >= 0) ? round((float)$fe, 2) : null;
                    if ($y === '' && !$d && !$st && !$en && $fe === null) continue;
                    $ins->execute([$newId, $y !== '' ? $y : null, $d, $st, $en, $fe, $order++]);
                }
                $pdo->commit();
            } catch (Exception $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $newId = 0;
                $error = 'Save wenne na: ' . $ex->getMessage();
            }

            if ($newId) {
                redirect(BASE_URL . '/admin/class-edit.php?id=' . $newId, 'Class added. Image ekak thiyenawanam methana upload karanna.', 'success');
            }
        }
    }
}

/* ---------- Dropdown data ---------- */
$teachers = $pdo->query("SELECT id, full_name FROM teachers WHERE status = 'active' ORDER BY full_name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name")->fetchAll();

/* ---------- Keep entered values if error ---------- */
$old = $error ? $_POST : [];
function ov($k) { global $old; return htmlspecialchars((string)($old[$k] ?? ''), ENT_QUOTES, 'UTF-8'); }
function osel($k, $v) { global $old; return (($old[$k] ?? '') == $v) ? 'selected' : ''; }

$schRows = [];
if ($error && !empty($_POST['sch_year'])) {
    foreach ($_POST['sch_year'] as $i => $y) {
        $schRows[] = [
            'year'  => $y,
            'day'   => $_POST['sch_day'][$i]   ?? '',
            'start' => $_POST['sch_start'][$i] ?? '',
            'end'   => $_POST['sch_end'][$i]   ?? '',
            'fee'   => $_POST['sch_fee'][$i]   ?? '',
        ];
    }
}
if (empty($schRows)) {
    $schRows[] = ['year' => '', 'day' => '', 'start' => '', 'end' => '', 'fee' => ''];
}

$pageTitle = 'Add Class';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><h4>Add Class</h4><a href="<?php echo asset('admin/classes.php'); ?>" class="btn btn-outline-secondary">Back</a></div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<form action="<?php echo asset('admin/class-add.php'); ?>" method="POST">
<?php echo csrfField(); ?><input type="hidden" name="action" value="add">

<!-- ================= Class details ================= -->
<div class="admin-card card mb-4"><div class="card-body">
<div class="row g-3">
<div class="col-md-8"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required value="<?php echo ov('title'); ?>"></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">
<option value="draft" <?php echo osel('status','draft'); ?>>Draft</option>
<option value="published" <?php echo osel('status','published'); ?>>Published</option>
<option value="archived" <?php echo osel('status','archived'); ?>>Archived</option>
</select></div>
<div class="col-12"><label class="form-label">Short Description</label><input type="text" name="short_description" class="form-control" value="<?php echo ov('short_description'); ?>"></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="5"><?php echo ov('description'); ?></textarea></div>
<div class="col-md-4"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">—</option><?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo osel('category_id', $c['id']); ?>><?php echo e($c['name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">—</option><?php foreach ($subjects as $s): ?><option value="<?php echo $s['id']; ?>" <?php echo osel('subject_id', $s['id']); ?>><?php echo e($s['name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Teacher</label><select name="teacher_id" class="form-select"><option value="">—</option><?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>" <?php echo osel('teacher_id', $t['id']); ?>><?php echo e($t['full_name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Class Type</label><select name="class_type" class="form-select">
<option value="physical" <?php echo osel('class_type','physical'); ?>>Physical</option>
<option value="online" <?php echo osel('class_type','online'); ?>>Online</option>
<option value="hybrid" <?php echo osel('class_type','hybrid'); ?>>Hybrid</option>
</select></div>
<div class="col-md-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?php echo ov('location'); ?>"></div>
<div class="col-md-3"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" value="<?php echo ov('duration'); ?>"></div>
<div class="col-md-3"><label class="form-label">Fee (default)</label><input type="number" name="fee" class="form-control" step="0.01" value="<?php echo ov('fee'); ?>"></div>
<div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="<?php echo ov('start_date'); ?>"></div>
<div class="col-md-4"><label class="form-label">Featured</label><div class="form-check mt-2"><input type="checkbox" name="featured" value="1" class="form-check-input" id="feat" <?php echo !empty($old['featured']) ? 'checked' : ''; ?>><label for="feat" class="form-check-label">Featured</label></div></div>
<div class="col-md-4"><label class="form-label">Image</label><div class="form-text mt-2">Class eka save una passe Edit page eke upload karanna.</div></div>
</div>
</div></div>

<!-- ================= Schedule (Year / Day / Time / Fee) ================= -->
<div class="admin-card card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Class Schedule</h5>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addSchRow">+ Add Year / Day / Time / Fee</button>
</div>
<p class="text-muted small">Year godak, day/time/fee godak add karanna puluwan. Ekak ekak "Remove" karanna puluwan.</p>

<div id="schRows">
<?php foreach ($schRows as $sc): ?>
    <div class="row g-2 align-items-end mb-2 sch-row">
        <div class="col-md-2"><label class="form-label small">Class Year</label>
            <input type="text" name="sch_year[]" class="form-control" maxlength="30" placeholder="e.g. 2027 A/L" value="<?php echo e($sc['year']); ?>"></div>
        <div class="col-md-2"><label class="form-label small">Class Day</label>
            <select name="sch_day[]" class="form-select"><option value="">—</option>
            <?php foreach ($days as $d): ?><option value="<?php echo $d; ?>" <?php echo $sc['day']===$d?'selected':''; ?>><?php echo $d; ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-2"><label class="form-label small">Start Time</label>
            <input type="time" name="sch_start[]" class="form-control" value="<?php echo e($sc['start']); ?>"></div>
        <div class="col-md-2"><label class="form-label small">End Time</label>
            <input type="time" name="sch_end[]" class="form-control" value="<?php echo e($sc['end']); ?>"></div>
        <div class="col-md-2"><label class="form-label small">Fee (Rs.)</label>
            <input type="number" name="sch_fee[]" class="form-control" step="0.01" min="0" placeholder="e.g. 3000" value="<?php echo e($sc['fee']); ?>"></div>
        <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 sch-remove">Remove</button></div>
    </div>
<?php endforeach; ?>
</div>

<template id="schTpl">
    <div class="row g-2 align-items-end mb-2 sch-row">
        <div class="col-md-2"><label class="form-label small">Class Year</label>
            <input type="text" name="sch_year[]" class="form-control" maxlength="30" placeholder="e.g. 2027 A/L"></div>
        <div class="col-md-2"><label class="form-label small">Class Day</label>
            <select name="sch_day[]" class="form-select"><option value="">—</option>
            <?php foreach ($days as $d): ?><option value="<?php echo $d; ?>"><?php echo $d; ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-2"><label class="form-label small">Start Time</label>
            <input type="time" name="sch_start[]" class="form-control"></div>
        <div class="col-md-2"><label class="form-label small">End Time</label>
            <input type="time" name="sch_end[]" class="form-control"></div>
        <div class="col-md-2"><label class="form-label small">Fee (Rs.)</label>
            <input type="number" name="sch_fee[]" class="form-control" step="0.01" min="0" placeholder="e.g. 3000"></div>
        <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 sch-remove">Remove</button></div>
    </div>
</template>

<button type="submit" class="btn btn-admin mt-3">Add Class</button>
</div></div>
</form>

<script>
(function () {
    var wrap = document.getElementById('schRows');
    var tpl  = document.getElementById('schTpl');
    document.getElementById('addSchRow').addEventListener('click', function () {
        wrap.appendChild(tpl.content.cloneNode(true));
    });
    wrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.sch-remove');
        if (!btn) return;
        var rows = wrap.querySelectorAll('.sch-row');
        var row = btn.closest('.sch-row');
        if (rows.length === 1) {
            row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            row.querySelectorAll('select').forEach(function (s) { s.value = ''; });
        } else {
            row.remove();
        }
    });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>