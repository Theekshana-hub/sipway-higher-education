<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = 'terms' AND status = 'published'");
$stmt->execute();
$page = $stmt->fetch();

$metaTitle = ($page['meta_title'] ?? 'Terms of Service') . ' | ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Terms of Service</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                <li class="breadcrumb-item active">Terms of Service</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if ($page && $page['content']): ?>
                    <?php echo $page['content']; ?>
                <?php else: ?>
                    <p>By accessing the SIPWAY Higher Education website, you agree to these Terms of Service.</p>
                    <h3>Use of Website</h3>
                    <p>Content on this website is for informational purposes. Unauthorized use, reproduction, or distribution is prohibited.</p>
                    <h3>Limitation of Liability</h3>
                    <p>SIPWAY Higher Education is not liable for any damages arising from the use of this website or reliance on its content.</p>
                    <h3>Changes</h3>
                    <p>We reserve the right to update these terms at any time. Continued use of the website constitutes acceptance of changes.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
