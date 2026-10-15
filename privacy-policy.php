<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = 'privacy-policy' AND status = 'published'");
$stmt->execute();
$page = $stmt->fetch();

$metaTitle = ($page['meta_title'] ?? 'Privacy Policy') . ' | ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Privacy Policy</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                <li class="breadcrumb-item active">Privacy Policy</li>
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
                    <p>This Privacy Policy describes how SIPWAY Higher Education collects, uses, and protects your personal information when you use our website.</p>
                    <h3>Information We Collect</h3>
                    <p>We may collect personal information such as name, email address, and phone number when you submit contact forms or inquiries.</p>
                    <h3>How We Use Your Information</h3>
                    <p>We use the information to respond to your inquiries, improve our services, and communicate educational opportunities.</p>
                    <h3>Data Protection</h3>
                    <p>We implement appropriate security measures to protect your personal data.</p>
                    <h3>Contact</h3>
                    <p>For privacy-related questions, contact us at <?php echo e(getSetting('contact_email')); ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
