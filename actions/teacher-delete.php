<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../admin/includes/auth.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    redirect(BASE_URL . '/admin/teachers.php', 'Invalid request.', 'error');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect(BASE_URL . '/admin/teachers.php', 'Invalid ID.', 'error');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    redirect(BASE_URL . '/admin/teachers.php', 'Teacher not found.', 'error');
}

// Unassign from classes (set teacher_id to null)
$pdo->prepare("UPDATE classes SET teacher_id = NULL WHERE teacher_id = ?")->execute([$id]);

// Delete image
if ($teacher['profile_image']) {
    deleteImage($teacher['profile_image']);
}

// Delete teacher (cascade removes teacher_subjects)
$pdo->prepare("DELETE FROM teachers WHERE id = ?")->execute([$id]);
logActivity('teacher_delete', "Deleted teacher: {$teacher['full_name']}", 'teacher', $id);

redirect(BASE_URL . '/admin/teachers.php', 'Teacher deleted successfully.');
