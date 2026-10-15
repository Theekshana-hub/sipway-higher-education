/**
 * SIPWAY Higher Education - Public Scripts (Enhanced)
 */
document.addEventListener('DOMContentLoaded', function () {

    // =============================================
    // 1. Smooth Scroll for Anchor Links
    // =============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const offset = 80; // navbar height
                const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    // =============================================
    // 2. Auto-dismiss Bootstrap Alerts
    // =============================================
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // =============================================
    // 3. Image Fallback
    // =============================================
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function () {
            if (!this.dataset.fallback) {
                this.dataset.fallback = '1';
                this.src = (window.BASE_URL || '') + '/assets/images/placeholder.png';
            }
        });
    });

    // =============================================
    // 4. Navbar Scroll Effect (shrink + shadow)
    // =============================================
    const navbar = document.querySelector('.sipway-navbar');
    if (navbar) {
        const handleScroll = () => {
            if (window.scrollY > 40) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll(); // run once on load
    }

    // =============================================
    // 5. Advanced Scroll Animations (Intersection Observer)
    // =============================================
    const observerOptions = {
        threshold: 0.12,
        rootMargin: '0px 0px -60px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Add staggered delay for multiple items
                const delay = entry.target.dataset.delay || 0;
                setTimeout(() => {
                    entry.target.classList.add('animate-fade-in');
                    entry.target.style.opacity = '1';
                }, delay);

                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Elements that should animate on scroll
    const animatedElements = document.querySelectorAll(
        '.sipway-card, .feature-box, .stat-item, .testimonial-card, .teacher-card, .section-header, .hero-section .lead, .hero-section h1'
    );

    animatedElements.forEach((el, i) => {
        el.style.opacity = '0';
        // Stagger delay (0ms, 80ms, 160ms...)
        el.dataset.delay = (i % 6) * 80;
        observer.observe(el);
    });

    // =============================================
    // 6. Counter Animation for Stats
    // =============================================
    const counters = document.querySelectorAll('.stat-number');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.textContent.replace(/\D/g, '')) || 0;
                const suffix = el.textContent.replace(/[0-9]/g, ''); // keep +, %, etc.
                let current = 0;
                const duration = 1800;
                const step = Math.ceil(target / (duration / 16));

                const update = () => {
                    current += step;
                    if (current >= target) {
                        el.textContent = target + suffix;
                        return;
                    }
                    el.textContent = current + suffix;
                    requestAnimationFrame(update);
                };
                update();
                counterObserver.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => counterObserver.observe(counter));

    // =============================================
    // 7. Active Nav Link on Scroll (optional)
    // =============================================
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.sipway-navbar .nav-link');

    if (sections.length && navLinks.length) {
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 120;
                if (window.scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        }, { passive: true });
    }

    // =============================================
    // 8. Button Ripple Effect (extra polish)
    // =============================================
    document.querySelectorAll('.btn-sipway, .btn-outline-sipway').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255,255,255,0.35);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;

            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });
    });

});

/* Ripple keyframe (add this to your CSS if not already present) */
/*
@keyframes ripple {
    to {
        transform: scale(2.5);
        opacity: 0;
    }
}
*/