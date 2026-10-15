<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/contact.php');
}

if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    redirect(BASE_URL . '/contact.php', 'Invalid security token. Please try again.', 'error');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '' || strlen($name) > 150) $errors[] = 'Valid name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($message === '' || strlen($message) > 2000) $errors[] = 'Message is required (max 2000 characters).';

if (!empty($errors)) {
    redirect(BASE_URL . '/contact.php', implode(' ', $errors), 'error');
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone ?: null, $subject ?: null, $message, $_SERVER['REMOTE_ADDR'] ?? null]);
    redirect(BASE_URL . '/contact.php', 'Thank you! Your message has been sent successfully.');
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    redirect(BASE_URL . '/contact.php', 'Sorry, something went wrong. Please try again later.', 'error');
}
