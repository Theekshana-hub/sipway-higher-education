<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();
$teachers = $pdo->query("SELECT id, full_name FROM teachers WHERE status = 'active' ORDER BY full_name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name")->fetchAll();
$pageTitle = 'Add Class';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><h4>Add Class</h4><a href="<?php echo asset('admin/classes.php'); ?>" class="btn btn-outline-secondary">Back</a></div>
<div class="admin-card card"><div class="card-body">
<form action="<?php echo asset('actions/class-save.php'); ?>" method="POST" enctype="multipart/form-data">
<?php echo csrfField(); ?><input type="hidden" name="action" value="add">
<div class="row g-3">
<div class="col-md-8"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft">Draft</option><option value="published">Published</option></select></div>
<div class="col-12"><label class="form-label">Short Description</label><input type="text" name="short_description" class="form-control" maxlength="500"></div>
<div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="5"></textarea></div>
<div class="col-md-4"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">—</option><?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo e($c['name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">—</option><?php foreach ($subjects as $s): ?><option value="<?php echo $s['id']; ?>"><?php echo e($s['name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Teacher</label><select name="teacher_id" class="form-select"><option value="">—</option><?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo e($t['full_name']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Class Type</label><select name="class_type" class="form-select"><option value="physical">Physical</option><option value="online">Online</option><option value="hybrid">Hybrid</option></select></div>
<div class="col-md-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" placeholder="e.g. 6 Months"></div>
<div class="col-md-3"><label class="form-label">Fee</label><input type="number" name="fee" class="form-control" step="0.01" min="0"></div>
<div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
<div class="col-md-4"><label class="form-label">Featured</label><div class="form-check mt-2"><input type="checkbox" name="featured" value="1" class="form-check-input" id="feat"><label for="feat" class="form-check-label">Featured</label></div></div>
<div class="col-12"><button type="submit" class="btn btn-admin">Save Class</button></div>
</div></form></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
