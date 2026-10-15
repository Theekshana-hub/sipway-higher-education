<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$metaTitle = 'Contact Us | ' . SITE_NAME;
$metaDescription = 'Get in touch with SIPWAY Higher Education.';
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                <li class="breadcrumb-item active">Contact</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <?php echo displayFlashMessage(); ?>
        <div class="row">
            <div class="col-lg-5 mb-4">
                <h3 class="mb-4">Get In Touch</h3>
                <div class="contact-info-item">
                    <div class="icon"><i class="bi bi-geo-alt"></i></div>
                    <div>
                        <strong>Address</strong>
                        <p class="mb-0"><?php echo e(getSetting('contact_address')); ?></p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="bi bi-telephone"></i></div>
                    <div>
                        <strong>Phone</strong>
                        <p class="mb-0"><a href="tel:<?php echo e(preg_replace('/\s+/', '', getSetting('contact_phone'))); ?>"><?php echo e(getSetting('contact_phone')); ?></a></p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="bi bi-envelope"></i></div>
                    <div>
                        <strong>Email</strong>
                        <p class="mb-0"><a href="mailto:<?php echo e(getSetting('contact_email')); ?>"><?php echo e(getSetting('contact_email')); ?></a></p>
                    </div>
                </div>
                <?php if ($wa = getSetting('contact_whatsapp')): ?>
                <div class="contact-info-item">
                    <div class="icon"><i class="bi bi-whatsapp"></i></div>
                    <div>
                        <strong>WhatsApp</strong>
                        <p class="mb-0"><a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $wa)); ?>" target="_blank"><?php echo e($wa); ?></a></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="social-links mt-4">
                    <?php if ($fb = getSetting('facebook_url')): ?><a href="<?php echo e($fb); ?>" target="_blank" class="me-2"><i class="fab fa-facebook fa-lg"></i></a><?php endif; ?>
                    <?php if ($tw = getSetting('twitter_url')): ?><a href="<?php echo e($tw); ?>" target="_blank" class="me-2"><i class="fab fa-twitter fa-lg"></i></a><?php endif; ?>
                    <?php if ($ig = getSetting('instagram_url')): ?><a href="<?php echo e($ig); ?>" target="_blank" class="me-2"><i class="fab fa-instagram fa-lg"></i></a><?php endif; ?>
                    <?php if ($li = getSetting('linkedin_url')): ?><a href="<?php echo e($li); ?>" target="_blank"><i class="fab fa-linkedin fa-lg"></i></a><?php endif; ?>
                </div>

                <div class="mt-4 ratio ratio-16x9">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.798511757686!2d79.8607!3d6.9271!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNsKwNTUnMzcuNiJOIDc5wrA1MSczOC41IkU!5e0!3m2!1sen!2slk!4v1600000000000" style="border:0; border-radius:12px;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card sipway-card">
                    <div class="card-body p-4">
                        <h4 class="mb-4">Send Us a Message</h4>
                        <form action="<?php echo asset('actions/contact-submit.php'); ?>" method="POST">
                            <?php echo csrfField(); ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="name" class="form-control" required maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" required maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" maxlength="30">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Subject</label>
                                    <input type="text" name="subject" class="form-control" maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message *</label>
                                    <textarea name="message" class="form-control" rows="5" required maxlength="2000"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sipway">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
