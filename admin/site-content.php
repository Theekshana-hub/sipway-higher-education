<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $fields = ['hero_heading','hero_description','hero_cta_primary','hero_cta_primary_link','hero_cta_secondary','hero_cta_secondary_link',
        'about_intro','vision','mission','why_choose_title','why_choose_content',
        'stat_students','stat_teachers','stat_classes','stat_years',
        'contact_phone','contact_email','contact_address','contact_whatsapp',
        'facebook_url','twitter_url','instagram_url','linkedin_url','footer_text',
        'meta_title','meta_description','site_name','site_tagline'];

    foreach ($fields as $key) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            setSetting($key, trim($_POST[$key]));
        }
    }
    logActivity('settings_update', 'Site content updated');
    redirect(BASE_URL . '/admin/site-content.php', 'Content saved successfully.');
}

$pageTitle = 'Site Content';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-4">Website Content Management</h4>
<form method="POST" action="<?php echo BASE_URL; ?>/admin/site-content.php">
<?php echo csrfField(); ?>
<div class="admin-card card mb-4"><div class="card-header">Hero Section</div><div class="card-body row g-3">
<div class="col-md-12"><label class="form-label">Heading</label><input type="text" name="hero_heading" class="form-control" value="<?php echo e(getSetting('hero_heading')); ?>"></div>
<div class="col-md-12"><label class="form-label">Description</label><textarea name="hero_description" class="form-control" rows="2"><?php echo e(getSetting('hero_description')); ?></textarea></div>
<div class="col-md-3"><label class="form-label">Primary CTA Label</label><input type="text" name="hero_cta_primary" class="form-control" value="<?php echo e(getSetting('hero_cta_primary')); ?>"></div>
<div class="col-md-3"><label class="form-label">Primary CTA Link</label><input type="text" name="hero_cta_primary_link" class="form-control" value="<?php echo e(getSetting('hero_cta_primary_link')); ?>"></div>
<div class="col-md-3"><label class="form-label">Secondary CTA Label</label><input type="text" name="hero_cta_secondary" class="form-control" value="<?php echo e(getSetting('hero_cta_secondary')); ?>"></div>
<div class="col-md-3"><label class="form-label">Secondary CTA Link</label><input type="text" name="hero_cta_secondary_link" class="form-control" value="<?php echo e(getSetting('hero_cta_secondary_link')); ?>"></div>
<div class="col-md-6"><label class="form-label">Hero Image (current)</label>
<div class="form-text mb-2">Image upload is disabled on this host (multipart forms are blocked).</div>
<?php if ($hi = getSetting('hero_image')): ?><img src="<?php echo getImageUrl($hi); ?>" height="60"><?php endif; ?></div>
</div></div>

<div class="admin-card card mb-4"><div class="card-header">About & Vision</div><div class="card-body row g-3">
<div class="col-12"><label class="form-label">About Intro</label><textarea name="about_intro" class="form-control" rows="2"><?php echo e(getSetting('about_intro')); ?></textarea></div>
<div class="col-md-6"><label class="form-label">Vision</label><textarea name="vision" class="form-control" rows="3"><?php echo e(getSetting('vision')); ?></textarea></div>
<div class="col-md-6"><label class="form-label">Mission</label><textarea name="mission" class="form-control" rows="3"><?php echo e(getSetting('mission')); ?></textarea></div>
<div class="col-md-6"><label class="form-label">Why Choose Title</label><input type="text" name="why_choose_title" class="form-control" value="<?php echo e(getSetting('why_choose_title')); ?>"></div>
<div class="col-md-6"><label class="form-label">Why Choose Content</label><textarea name="why_choose_content" class="form-control" rows="2"><?php echo e(getSetting('why_choose_content')); ?></textarea></div>
</div></div>

<div class="admin-card card mb-4"><div class="card-header">Statistics</div><div class="card-body row g-3">
<div class="col-md-3"><label class="form-label">Students</label><input type="text" name="stat_students" class="form-control" value="<?php echo e(getSetting('stat_students')); ?>"></div>
<div class="col-md-3"><label class="form-label">Teachers</label><input type="text" name="stat_teachers" class="form-control" value="<?php echo e(getSetting('stat_teachers')); ?>"></div>
<div class="col-md-3"><label class="form-label">Classes</label><input type="text" name="stat_classes" class="form-control" value="<?php echo e(getSetting('stat_classes')); ?>"></div>
<div class="col-md-3"><label class="form-label">Years</label><input type="text" name="stat_years" class="form-control" value="<?php echo e(getSetting('stat_years')); ?>"></div>
</div></div>

<div class="admin-card card mb-4"><div class="card-header">Contact Details</div><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="contact_phone" class="form-control" value="<?php echo e(getSetting('contact_phone')); ?>"></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control" value="<?php echo e(getSetting('contact_email')); ?>"></div>
<div class="col-md-6"><label class="form-label">Address</label><input type="text" name="contact_address" class="form-control" value="<?php echo e(getSetting('contact_address')); ?>"></div>
<div class="col-md-6"><label class="form-label">WhatsApp</label><input type="text" name="contact_whatsapp" class="form-control" value="<?php echo e(getSetting('contact_whatsapp')); ?>"></div>
<div class="col-md-3"><label class="form-label">Facebook</label><input type="url" name="facebook_url" class="form-control" value="<?php echo e(getSetting('facebook_url')); ?>"></div>
<div class="col-md-3"><label class="form-label">Twitter</label><input type="url" name="twitter_url" class="form-control" value="<?php echo e(getSetting('twitter_url')); ?>"></div>
<div class="col-md-3"><label class="form-label">Instagram</label><input type="url" name="instagram_url" class="form-control" value="<?php echo e(getSetting('instagram_url')); ?>"></div>
<div class="col-md-3"><label class="form-label">LinkedIn</label><input type="url" name="linkedin_url" class="form-control" value="<?php echo e(getSetting('linkedin_url')); ?>"></div>
<div class="col-12"><label class="form-label">Footer Text</label><input type="text" name="footer_text" class="form-control" value="<?php echo e(getSetting('footer_text')); ?>"></div>
</div></div>

<div class="admin-card card mb-4"><div class="card-header">SEO</div><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?php echo e(getSetting('site_name')); ?>"></div>
<div class="col-md-6"><label class="form-label">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?php echo e(getSetting('site_tagline')); ?>"></div>
<div class="col-md-6"><label class="form-label">Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?php echo e(getSetting('meta_title')); ?>"></div>
<div class="col-md-6"><label class="form-label">Meta Description</label><textarea name="meta_description" class="form-control" rows="2"><?php echo e(getSetting('meta_description')); ?></textarea></div>
</div></div>

<button type="submit" class="btn btn-admin btn-lg">Save All Content</button>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>