<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$id]);
$class = $stmt->fetch();
if (!$class) redirect(BASE_URL . '/admin/classes.php', 'Class not found.', 'error');

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

/* Class Year max length (DB column VARCHAR(255) wenna oni) */
define('SCH_YEAR_MAX', 255);

/* ---------- Save Schedule (this page handles it itself) ---------- */
function schCsrfOk() {
    $html = (string)csrfField();
    if (!preg_match('/name=["\']([^"\']+)["\']/', $html, $n) || !preg_match('/value=["\']([^"\']*)["\']/', $html, $v)) {
        return true;
    }
    $posted = $_POST[$n[1]] ?? '';
    return is_string($posted) && hash_equals($v[1], $posted);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_schedule') {
    if (!schCsrfOk()) {
        redirect(BASE_URL . '/admin/class-edit.php?id=' . $id, 'Invalid request token.', 'error');
    }
    $years  = $_POST['sch_year']  ?? [];
    $dayArr = $_POST['sch_day']   ?? [];
    $starts = $_POST['sch_start'] ?? [];
    $ends   = $_POST['sch_end']   ?? [];
    $fees   = $_POST['sch_fee']   ?? [];

    $saved = false;
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM class_year_schedules WHERE class_id = ?")->execute([$id]);
        $ins = $pdo->prepare("INSERT INTO class_year_schedules (class_id, class_year, class_day, start_time, end_time, fee, sort_order) VALUES (?,?,?,?,?,?,?)");
        $order = 0;
        foreach ($years as $i => $y) {
            $y  = mb_substr(trim($y), 0, SCH_YEAR_MAX);
            $d  = $dayArr[$i] ?? '';
            $d  = in_array($d, $days, true) ? $d : null;
            $st = $starts[$i] ?? '';
            $st = preg_match('/^\d{2}:\d{2}$/', $st) ? $st . ':00' : null;
            $en = $ends[$i] ?? '';
            $en = preg_match('/^\d{2}:\d{2}$/', $en) ? $en . ':00' : null;
            $fe = trim((string)($fees[$i] ?? ''));
            $fe = (is_numeric($fe) && (float)$fe >= 0) ? round((float)$fe, 2) : null;
            if ($y === '' && !$d && !$st && !$en && $fe === null) continue; // empty row skip
            $ins->execute([$id, $y !== '' ? $y : null, $d, $st, $en, $fe, $order++]);
        }
        $pdo->commit();
        $saved = true;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Schedule save error: ' . $ex->getMessage());
    }
    if ($saved) {
        redirect(BASE_URL . '/admin/class-edit.php?id=' . $id, 'Schedule saved.', 'success');
    } else {
        redirect(BASE_URL . '/admin/class-edit.php?id=' . $id, 'Could not save schedule.', 'error');
    }
}

$teachers = $pdo->query("SELECT id, full_name FROM teachers WHERE status = 'active' ORDER BY full_name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name")->fetchAll();

$schStmt = $pdo->prepare("SELECT * FROM class_year_schedules WHERE class_id = ? ORDER BY sort_order, id");
$schStmt->execute([$id]);
$schedules = $schStmt->fetchAll();
if (empty($schedules)) {
    $schedules = [['class_year' => '', 'class_day' => '', 'start_time' => '', 'end_time' => '', 'fee' => '']];
}

$pageTitle = 'Edit Class';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><h4>Edit Class</h4><a href="<?php echo asset('admin/classes.php'); ?>" class="btn btn-outline-secondary">Back</a></div>

<!-- ================= Main class details ================= -->
<div class="admin-card card mb-4"><div class="card-body">
<form action="<?php echo asset('actions/class-save.php'); ?>" method="POST" enctype="multipart/form-data">
<?php echo csrfField(); ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?php echo $id; ?>">
<div class="row g-3">
<div class="col-md-8"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required value="<?php echo e($class['title']); ?>"></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">
<option value="draft" <?php echo $class['status']==='draft'?'selected':''; ?>>Draft</option>
<option value="published" <?php echo $class['status']==='published'?'selected':''; ?>>Published</option>
<option value="archived" <?php echo $class['status']==='archived'?'selected':''; ?>>Archived</option>
</select></div>
<div class="col-12"><label class="form-label">Short Description</label><input type="text" name="short_description" class="form-control" value="<?php echo e($class['short_description']); ?>"></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="5"><?php echo e($class['description']); ?></textarea></div>
<div class="col-md-4"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">—</option><?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $class['category_id']==$c['id']?'selected':''; ?>><?php echo e($c['name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">—</option><?php foreach ($subjects as $s): ?><option value="<?php echo $s['id']; ?>" <?php echo $class['subject_id']==$s['id']?'selected':''; ?>><?php echo e($s['name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Teacher</label><select name="teacher_id" class="form-select"><option value="">—</option><?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>" <?php echo $class['teacher_id']==$t['id']?'selected':''; ?>><?php echo e($t['full_name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Class Type</label><select name="class_type" class="form-select">
<option value="physical" <?php echo $class['class_type']==='physical'?'selected':''; ?>>Physical</option>
<option value="online" <?php echo $class['class_type']==='online'?'selected':''; ?>>Online</option>
<option value="hybrid" <?php echo $class['class_type']==='hybrid'?'selected':''; ?>>Hybrid</option>
</select></div>
<div class="col-md-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?php echo e($class['location']); ?>"></div>
<div class="col-md-3"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" value="<?php echo e($class['duration']); ?>"></div>
<div class="col-md-3"><label class="form-label">Fee (default)</label><input type="number" name="fee" class="form-control" step="0.01" value="<?php echo e($class['fee']); ?>"></div>
<div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="<?php echo e($class['start_date']); ?>"></div>
<div class="col-md-4"><label class="form-label">Image</label><?php if ($class['image']): ?><div class="mb-1"><img src="<?php echo getImageUrl($class['image']); ?>" height="50"></div><?php endif; ?><input type="file" name="image" class="form-control" accept="image/*"></div>
<div class="col-md-4"><label class="form-label">Featured</label><div class="form-check mt-2"><input type="checkbox" name="featured" value="1" class="form-check-input" id="feat" <?php echo $class['featured']?'checked':''; ?>><label for="feat" class="form-check-label">Featured</label></div></div>
<div class="col-12"><button type="submit" class="btn btn-admin">Update Class</button></div>
</div></form></div></div>

<!-- ================= Schedule (Year / Day / Time / Fee) ================= -->
<div class="admin-card card"><div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Class Schedule</h5>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addSchRow">+ Add Year / Day / Time / Fee</button>
</div>
<p class="text-muted small">Year godak, day/time/fee godak add karanna puluwan. Ekak ekak "✕" karanna puluwan. Ain karala <strong>Save Schedule</strong> click karanna.</p>

<form method="POST" action="<?php echo asset('admin/class-edit.php?id=' . $id); ?>">
<?php echo csrfField(); ?>
<input type="hidden" name="action" value="save_schedule">

<div id="schRows">
<?php foreach ($schedules as $sc): ?>
    <div class="row g-2 align-items-end mb-2 sch-row">
        <div class="col-md-3"><label class="form-label small">Class Year</label>
            <input type="text" name="sch_year[]" class="form-control" maxlength="<?php echo SCH_YEAR_MAX; ?>" placeholder="e.g. 2027 A/L" value="<?php echo e($sc['class_year'] ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label small">Class Day</label>
            <select name="sch_day[]" class="form-select"><option value="">—</option>
            <?php foreach ($days as $d): ?><option value="<?php echo $d; ?>" <?php echo ($sc['class_day'] ?? '')===$d?'selected':''; ?>><?php echo $d; ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-2"><label class="form-label small">Start Time</label>
            <input type="time" name="sch_start[]" class="form-control" value="<?php echo !empty($sc['start_time']) ? e(substr($sc['start_time'],0,5)) : ''; ?>"></div>
        <div class="col-md-2"><label class="form-label small">End Time</label>
            <input type="time" name="sch_end[]" class="form-control" value="<?php echo !empty($sc['end_time']) ? e(substr($sc['end_time'],0,5)) : ''; ?>"></div>
        <div class="col-md-2"><label class="form-label small">Fee (Rs.)</label>
            <input type="number" name="sch_fee[]" class="form-control" step="0.01" min="0" placeholder="e.g. 3000" value="<?php echo ($sc['fee'] ?? '') !== '' && $sc['fee'] !== null ? e($sc['fee']) : ''; ?>"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 sch-remove" title="Remove">✕</button></div>
    </div>
<?php endforeach; ?>
</div>

<template id="schTpl">
    <div class="row g-2 align-items-end mb-2 sch-row">
        <div class="col-md-3"><label class="form-label small">Class Year</label>
            <input type="text" name="sch_year[]" class="form-control" maxlength="<?php echo SCH_YEAR_MAX; ?>" placeholder="e.g. 2027 A/L"></div>
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
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 sch-remove" title="Remove">✕</button></div>
    </div>
</template>

<button type="submit" class="btn btn-admin mt-2">Save Schedule</button>
</form>
</div></div>

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