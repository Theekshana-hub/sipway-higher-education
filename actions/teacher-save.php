<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../admin/includes/auth.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    redirect(BASE_URL . '/admin/teachers.php', 'Invalid request.', 'error');
}

$pdo = getDB();
$action = $_POST['action'] ?? '';
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '') ?: null;
$phone = trim($_POST['phone'] ?? '') ?: null;
$qualification = trim($_POST['qualification'] ?? '') ?: null;
$experience = (int)($_POST['experience_years'] ?? 0);
$bio = trim($_POST['bio'] ?? '') ?: null;
$specialization = trim($_POST['specialization'] ?? '') ?: null;
$status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
$featured = isset($_POST['featured']) ? 1 : 0;
$sortOrder = (int)($_POST['sort_order'] ?? 0);
$linkedin = trim($_POST['linkedin'] ?? '') ?: null;
$twitter = trim($_POST['twitter'] ?? '') ?: null;
$facebook = trim($_POST['facebook'] ?? '') ?: null;
$subjects = $_POST['subjects'] ?? [];

if ($fullName === '') {
    redirect(BASE_URL . '/admin/teachers.php', 'Name is required.', 'error');
}

$slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $fullName));
$slug = trim($slug, '-');

$imagePath = null;
if (!empty($_FILES['profile_image']['name'])) {
    $upload = uploadImage($_FILES['profile_image'], TEACHERS_UPLOAD, 'teacher_');
    if (!$upload['success']) {
        redirect(BASE_URL . '/admin/teachers.php', $upload['message'], 'error');
    }
    $imagePath = $upload['path'];
}

try {
    if ($action === 'add') {
        // Ensure unique slug
        $baseSlug = $slug;
        $i = 1;
        while (true) {
            $check = $pdo->prepare("SELECT id FROM teachers WHERE slug = ?");
            $check->execute([$slug]);
            if (!$check->fetch()) break;
            $slug = $baseSlug . '-' . $i++;
        }

        $stmt = $pdo->prepare("INSERT INTO teachers (full_name, slug, email, phone, qualification, experience_years, bio, specialization, profile_image, facebook, twitter, linkedin, status, featured, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$fullName, $slug, $email, $phone, $qualification, $experience, $bio, $specialization, $imagePath, $facebook, $twitter, $linkedin, $status, $featured, $sortOrder]);
        $teacherId = $pdo->lastInsertId();
        logActivity('teacher_create', "Created teacher: $fullName", 'teacher', $teacherId);
        $msg = 'Teacher added successfully.';
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            redirect(BASE_URL . '/admin/teachers.php', 'Teacher not found.', 'error');
        }

        if ($imagePath && $existing['profile_image']) {
            deleteImage($existing['profile_image']);
        }
        $finalImage = $imagePath ?: $existing['profile_image'];

        $stmt = $pdo->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, qualification=?, experience_years=?, bio=?, specialization=?, profile_image=?, facebook=?, twitter=?, linkedin=?, status=?, featured=?, sort_order=? WHERE id=?");
        $stmt->execute([$fullName, $email, $phone, $qualification, $experience, $bio, $specialization, $finalImage, $facebook, $twitter, $linkedin, $status, $featured, $sortOrder, $id]);
        $teacherId = $id;
        // Clear subjects
        $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?")->execute([$id]);
        logActivity('teacher_update', "Updated teacher: $fullName", 'teacher', $id);
        $msg = 'Teacher updated successfully.';
    } else {
        redirect(BASE_URL . '/admin/teachers.php', 'Invalid action.', 'error');
    }

    // Assign subjects
    if (!empty($subjects) && is_array($subjects)) {
        $ins = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
        foreach ($subjects as $sid) {
            $ins->execute([$teacherId, (int)$sid]);
        }
    }

    redirect(BASE_URL . '/admin/teachers.php', $msg);
} catch (Exception $e) {
    error_log('Teacher save error: ' . $e->getMessage());
    redirect(BASE_URL . '/admin/teachers.php', 'An error occurred while saving.', 'error');
}
