<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../admin/includes/auth.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    redirect(BASE_URL . '/admin/classes.php', 'Invalid request.', 'error');
}

$pdo = getDB();
$action = $_POST['action'] ?? '';
$title = trim($_POST['title'] ?? '');
if ($title === '') redirect(BASE_URL . '/admin/classes.php', 'Title required.', 'error');

$slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
$slug = trim($slug, '-');
$short = trim($_POST['short_description'] ?? '') ?: null;
$desc = trim($_POST['description'] ?? '') ?: null;
$catId = (int)($_POST['category_id'] ?? 0) ?: null;
$subId = (int)($_POST['subject_id'] ?? 0) ?: null;
$teacherId = (int)($_POST['teacher_id'] ?? 0) ?: null;
$type = in_array($_POST['class_type'] ?? '', ['online','physical','hybrid']) ? $_POST['class_type'] : 'physical';
$location = trim($_POST['location'] ?? '') ?: null;
$duration = trim($_POST['duration'] ?? '') ?: null;
$fee = $_POST['fee'] !== '' ? (float)$_POST['fee'] : null;
$status = in_array($_POST['status'] ?? '', ['published','draft','archived']) ? $_POST['status'] : 'draft';
$featured = isset($_POST['featured']) ? 1 : 0;
$startDate = $_POST['start_date'] ?: null;

$imagePath = null;
if (!empty($_FILES['image']['name'])) {
    $upload = uploadImage($_FILES['image'], CLASSES_UPLOAD, 'class_');
    if (!$upload['success']) redirect(BASE_URL . '/admin/classes.php', $upload['message'], 'error');
    $imagePath = $upload['path'];
}

try {
    if ($action === 'add') {
        $base = $slug; $i = 1;
        while (true) {
            $c = $pdo->prepare("SELECT id FROM classes WHERE slug = ?");
            $c->execute([$slug]);
            if (!$c->fetch()) break;
            $slug = $base . '-' . $i++;
        }
        $stmt = $pdo->prepare("INSERT INTO classes (title, slug, description, short_description, category_id, subject_id, teacher_id, image, class_type, location, duration, fee, status, featured, start_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$title, $slug, $desc, $short, $catId, $subId, $teacherId, $imagePath, $type, $location, $duration, $fee, $status, $featured, $startDate]);
        logActivity('class_create', "Created class: $title", 'class', $pdo->lastInsertId());
        redirect(BASE_URL . '/admin/classes.php', 'Class added successfully.');
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        $ex = $stmt->fetch();
        if (!$ex) redirect(BASE_URL . '/admin/classes.php', 'Not found.', 'error');
        if ($imagePath && $ex['image']) deleteImage($ex['image']);
        $finalImg = $imagePath ?: $ex['image'];
        $stmt = $pdo->prepare("UPDATE classes SET title=?, description=?, short_description=?, category_id=?, subject_id=?, teacher_id=?, image=?, class_type=?, location=?, duration=?, fee=?, status=?, featured=?, start_date=? WHERE id=?");
        $stmt->execute([$title, $desc, $short, $catId, $subId, $teacherId, $finalImg, $type, $location, $duration, $fee, $status, $featured, $startDate, $id]);
        logActivity('class_update', "Updated class: $title", 'class', $id);
        redirect(BASE_URL . '/admin/classes.php', 'Class updated successfully.');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    redirect(BASE_URL . '/admin/classes.php', 'Error saving class.', 'error');
}
redirect(BASE_URL . '/admin/classes.php');
