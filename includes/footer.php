<?php
$logoPath = getSetting('logo_path', 'assets/images/logo.png');
$phone    = getSetting('contact_phone');
$email    = getSetting('contact_email');
$address  = getSetting('contact_address');
?>
<footer class="sipway-footer" role="contentinfo">
    <div class="container">
        <div class="row g-5 footer-main">

            <!-- Brand -->
            <div class="col-lg-5 col-md-12">
                <a href="<?php echo asset('index.php'); ?>" class="footer-brand d-inline-block mb-3">
                    <img src="<?php echo asset($logoPath); ?>" alt="SIPWAY" height="52" class="footer-logo" onerror="this.style.display='none'">
                </a>
                <p class="footer-text"><?php echo e(getSetting('footer_text', 'SIPWAY Higher Education — Empowering minds, shaping futures.')); ?></p>

                <div class="social-links">
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

            <!-- Quick links -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo asset('index.php'); ?>">Home</a></li>
                    <li><a href="<?php echo asset('about.php'); ?>">About Us</a></li>
                    <li><a href="<?php echo asset('classes.php'); ?>">Classes</a></li>
                    <li><a href="<?php echo asset('teachers.php'); ?>">Teachers</a></li>
                    <li><a href="<?php echo asset('contact.php'); ?>">Contact</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-4 col-md-6">
                <h5 class="footer-heading">Contact Us</h5>
                <ul class="footer-contact">
                    <?php if ($address): ?>
                        <li>
                            <span class="contact-icon"><i class="bi bi-geo-alt"></i></span>
                            <span><?php echo e($address); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                        <li>
                            <span class="contact-icon"><i class="bi bi-telephone"></i></span>
                            <a href="tel:<?php echo e(preg_replace('/\s+/', '', $phone)); ?>"><?php echo e($phone); ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ($email): ?>
                        <li>
                            <span class="contact-icon"><i class="bi bi-envelope"></i></span>
                            <a href="mailto:<?php echo e($email); ?>"><?php echo e($email); ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ($wa = getSetting('contact_whatsapp')): ?>
                        <li>
                            <span class="contact-icon"><i class="bi bi-whatsapp"></i></span>
                            <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $wa)); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="footer-bottom">
            <div class="row align-items-center gy-2">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> SIPWAY Higher Education. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end footer-legal">
                    <a href="<?php echo asset('privacy-policy.php'); ?>">Privacy Policy</a>
                    <a href="<?php echo asset('terms.php'); ?>">Terms of Service</a>
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