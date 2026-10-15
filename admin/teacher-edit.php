<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(BASE_URL . '/admin/teachers.php', 'Invalid teacher.', 'error');
}

$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch();
if (!$teacher) {
    redirect(BASE_URL . '/admin/teachers.php', 'Teacher not found.', 'error');
}

$subjects = $pdo->query("SELECT * FROM subjects WHERE status = 'active' ORDER BY name")->fetchAll();
$assigned = $pdo->prepare("SELECT subject_id FROM teacher_subjects WHERE teacher_id = ?");
$assigned->execute([$id]);
$assignedIds = $assigned->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Edit Teacher';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Edit Teacher</h4>
    <a href="<?php echo asset('admin/teachers.php'); ?>" class="btn btn-outline-secondary">Back</a>
</div>

<div class="admin-card card">
    <div class="card-body">
        <form action="<?php echo asset('actions/teacher-save.php'); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required value="<?php echo e($teacher['full_name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo e($teacher['email']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo e($teacher['phone']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Qualification</label>
                    <input type="text" name="qualification" class="form-control" value="<?php echo e($teacher['qualification']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Experience (years)</label>
                    <input type="number" name="experience_years" class="form-control" min="0" value="<?php echo (int)$teacher['experience_years']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="<?php echo e($teacher['specialization']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $teacher['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $teacher['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Biography</label>
                    <textarea name="bio" class="form-control" rows="4"><?php echo e($teacher['bio']); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Profile Image</label>
                    <?php if ($teacher['profile_image']): ?>
                        <div class="mb-2"><img src="<?php echo getImageUrl($teacher['profile_image']); ?>" height="80" class="rounded"></div>
                    <?php endif; ?>
                    <input type="file" name="profile_image" class="form-control" accept="image/*" data-preview="imgPreview">
                    <img id="imgPreview" src="" style="display:none;max-height:100px;margin-top:8px" class="rounded">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Featured</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="featured" value="1" class="form-check-input" id="featured" <?php echo $teacher['featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="featured">Show on homepage</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$teacher['sort_order']; ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Subjects</label>
                    <div class="row">
                        <?php foreach ($subjects as $s): ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="subjects[]" value="<?php echo $s['id']; ?>" class="form-check-input" id="sub<?php echo $s['id']; ?>" <?php echo in_array($s['id'], $assignedIds) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sub<?php echo $s['id']; ?>"><?php echo e($s['name']); ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">LinkedIn</label>
                    <input type="url" name="linkedin" class="form-control" value="<?php echo e($teacher['linkedin']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Twitter</label>
                    <input type="url" name="twitter" class="form-control" value="<?php echo e($teacher['twitter']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Facebook</label>
                    <input type="url" name="facebook" class="form-control" value="<?php echo e($teacher['facebook']); ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin">Update Teacher</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
