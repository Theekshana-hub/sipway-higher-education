<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pageTitle = 'Media';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-4">Media Library</h4>
<p class="text-muted">Images are uploaded when managing teachers, classes, and site content. Upload directories:</p>
<ul>
<li><code>uploads/teachers/</code></li>
<li><code>uploads/classes/</code></li>
<li><code>uploads/banners/</code></li>
<li><code>uploads/pages/</code></li>
<li><code>uploads/gallery/</code></li>
</ul>
<p>Ensure these folders are writable (chmod 755 or 775).</p>
<?php include __DIR__ . '/includes/footer.php'; ?>
