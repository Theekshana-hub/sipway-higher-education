<?php
if (!defined('SIPWAY_APP')) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../functions.php';
}
$metaTitle = $metaTitle ?? getSetting('meta_title', SITE_NAME);
$metaDescription = $metaDescription ?? getSetting('meta_description', '');
$logoPath = getSetting('logo_path', 'assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($metaTitle); ?></title>
    <meta name="description" content="<?php echo e($metaDescription); ?>">
    <link rel="icon" href="<?php echo asset(getSetting('favicon_path', 'assets/images/favicon.ico')); ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- SIPWAY CSS -->
    <link href="<?php echo asset('assets/css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('assets/css/responsive.css'); ?>" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
