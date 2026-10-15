<?php
if (!defined('SIPWAY_APP')) {
    define('SIPWAY_APP', true);
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/functions.php';
}
http_response_code(404);
$metaTitle = 'Page Not Found | ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container text-center py-5">
        <h1 class="display-1 text-sipway fw-bold">404</h1>
        <h2 class="mb-3">Page Not Found</h2>
        <p class="text-muted mb-4">The page you are looking for does not exist or has been moved.</p>
        <a href="<?php echo asset('index.php'); ?>" class="btn btn-sipway">Back to Home</a>
        <a href="<?php echo asset('classes.php'); ?>" class="btn btn-outline-sipway ms-2">Browse Classes</a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
