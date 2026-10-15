<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../admin/includes/auth.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    redirect(BASE_URL . '/admin/classes.php', 'Invalid request.', 'error');
}
$id = (int)($_POST['id'] ?? 0);
$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$id]);
$class = $stmt->fetch();
if (!$class) redirect(BASE_URL . '/admin/classes.php', 'Not found.', 'error');
if ($class['image']) deleteImage($class['image']);
$pdo->prepare("DELETE FROM classes WHERE id = ?")->execute([$id]);
logActivity('class_delete', "Deleted class: {$class['title']}", 'class', $id);
redirect(BASE_URL . '/admin/classes.php', 'Class deleted.');
