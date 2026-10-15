<?php
if (!defined('SIPWAY_APP')) {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../functions.php';
}
$admin = getCurrentAdmin();
$pageTitle = $pageTitle ?? 'Dashboard';
$logoPath = getSetting('logo_path', 'assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | SIPWAY Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo asset('assets/css/admin.css'); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">
<div class="sidebar-overlay"></div>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="admin-main">
<?php include __DIR__ . '/navbar.php'; ?>
<main class="admin-content">
<?php echo displayFlashMessage(); ?>
