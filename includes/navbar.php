<?php
$logoPath = getSetting('logo_path', 'assets/images/logo.png');
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top sipway-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo asset('index.php'); ?>">
            <img src="<?php echo asset($logoPath); ?>" alt="SIPWAY Higher Education" class="logo-img" height="48" onerror="this.src='<?php echo asset('assets/images/logo-placeholder.png'); ?>'">
            <span class="ms-2 brand-text d-none d-sm-inline">SIPWAY HIGHER EDUCATION</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('index.php'); ?>" href="<?php echo asset('index.php'); ?>">Home21</a>
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
                <li class="nav-item ms-lg-3">
                    <form class="d-flex search-form" action="<?php echo asset('search.php'); ?>" method="GET">
                        <input class="form-control form-control-sm" type="search" name="q" placeholder="Search..." aria-label="Search" value="<?php echo e($_GET['q'] ?? ''); ?>">
                        <button class="btn btn-sm btn-outline-light ms-1" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
