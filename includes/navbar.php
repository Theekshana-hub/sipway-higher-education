<?php
$logoPath = getSetting('logo_path', 'assets/images/logo.png');
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top sipway-navbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo asset('index.php'); ?>">
            <img src="<?php echo asset($logoPath); ?>" 
                 alt="SIPWAY Higher Education" 
                 class="logo-img" 
                 height="48"
                 onerror="this.src='<?php echo asset('assets/images/logo.svg'); ?>'">
            
            <div class="brand-text-wrap ms-2 d-none d-sm-flex flex-column">
                <span class="brand-title">SIPWAY</span>
                <span class="brand-subtitle">HIGHER EDUCATION</span>
            </div>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#mainNavbar" aria-controls="mainNavbar" 
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('index.php'); ?>" href="<?php echo asset('index.php'); ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('about.php'); ?>" href="<?php echo asset('about.php'); ?>">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('classes.php'); ?>" href="<?php echo asset('classes.php'); ?>">Classes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('teachers.php'); ?>" href="<?php echo asset('teachers.php'); ?>">Teachers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('contact.php'); ?>" href="<?php echo asset('contact.php'); ?>">Contact</a>
                </li>

                <!-- Search -->
               

                <!-- CTA Button -->
          
            </ul>
        </div>
    </div>
</nav>