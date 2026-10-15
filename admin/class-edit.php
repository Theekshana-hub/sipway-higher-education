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
$teachers = $pdo->query("SELECT id, full_name FROM teachers WHERE status = 'active' ORDER BY full_name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name")->fetchAll();
$pageTitle = 'Edit Class';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><h4>Edit Class</h4><a href="<?php echo asset('admin/classes.php'); ?>" class="btn btn-outline-secondary">Back</a></div>
<div class="admin-card card"><div class="card-body">
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
<div class="col-md-3"><label class="form-label">Fee</label><input type="number" name="fee" class="form-control" step="0.01" value="<?php echo e($class['fee']); ?>"></div>
<div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="<?php echo e($class['start_date']); ?>"></div>
<div class="col-md-4"><label class="form-label">Image</label><?php if ($class['image']): ?><div class="mb-1"><img src="<?php echo getImageUrl($class['image']); ?>" height="50"></div><?php endif; ?><input type="file" name="image" class="form-control" accept="image/*"></div>
<div class="col-md-4"><label class="form-label">Featured</label><div class="form-check mt-2"><input type="checkbox" name="featured" value="1" class="form-check-input" id="feat" <?php echo $class['featured']?'checked':''; ?>><label for="feat" class="form-check-label">Featured</label></div></div>
<div class="col-12"><button type="submit" class="btn btn-admin">Update Class</button></div>
</div></form></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
