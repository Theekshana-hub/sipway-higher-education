<?php
$logoPath = getSetting('logo_path', 'assets/images/logo.png');
?>
<footer class="sipway-footer">
    <div class="container">
        <div class="row g-4 py-5">
            <div class="col-lg-4 col-md-6">
                <a href="<?php echo asset('index.php'); ?>" class="d-inline-block mb-3">
                    <img src="<?php echo asset($logoPath); ?>" alt="SIPWAY" height="50" class="footer-logo" onerror="this.style.display='none'">
                </a>
                <p class="footer-text"><?php echo e(getSetting('footer_text', 'SIPWAY Higher Education — Empowering minds, shaping futures.')); ?></p>
                <div class="social-links mt-3">
                    <?php if ($fb = getSetting('facebook_url')): ?>
                        <a href="<?php echo e($fb); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if ($tw = getSetting('twitter_url')): ?>
                        <a href="<?php echo e($tw); ?>" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <?php endif; ?>
                    <?php if ($ig = getSetting('instagram_url')): ?>
                        <a href="<?php echo e($ig); ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if ($li = getSetting('linkedin_url')): ?>
                        <a href="<?php echo e($li); ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                    <li><a href="<?php echo asset('about.php'); ?>">About Us</a></li>
                    <li><a href="<?php echo asset('classes.php'); ?>">Classes</a></li>
                    <li><a href="<?php echo asset('teachers.php'); ?>">Teachers</a></li>
                    <li><a href="<?php echo asset('contact.php'); ?>">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Programs</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo asset('classes.php?category=undergraduate'); ?>">Undergraduate</a></li>
                    <li><a href="<?php echo asset('classes.php?category=postgraduate'); ?>">Postgraduate</a></li>
                    <li><a href="<?php echo asset('classes.php?category=professional'); ?>">Professional</a></li>
                    <li><a href="<?php echo asset('classes.php?category=short-courses'); ?>">Short Courses</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Contact Us</h5>
                <ul class="footer-contact">
                    <li><i class="bi bi-geo-alt"></i> <?php echo e(getSetting('contact_address')); ?></li>
                    <li><i class="bi bi-telephone"></i> <a href="tel:<?php echo e(preg_replace('/\s+/', '', getSetting('contact_phone'))); ?>"><?php echo e(getSetting('contact_phone')); ?></a></li>
                    <li><i class="bi bi-envelope"></i> <a href="mailto:<?php echo e(getSetting('contact_email')); ?>"><?php echo e(getSetting('contact_email')); ?></a></li>
                    <?php if ($wa = getSetting('contact_whatsapp')): ?>
                        <li><i class="bi bi-whatsapp"></i> <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $wa)); ?>" target="_blank">WhatsApp</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom py-3 border-top border-secondary">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 small">&copy; <?php echo date('Y'); ?> SIPWAY Higher Education. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="<?php echo asset('privacy-policy.php'); ?>" class="small me-3">Privacy Policy</a>
                    <a href="<?php echo asset('terms.php'); ?>" class="small">Terms of Service</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SIPWAY JS -->
<script src="<?php echo asset('assets/js/script.js'); ?>"></script>
</body>
</html>
