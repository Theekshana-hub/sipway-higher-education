<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$logoPath = getSetting('logo_path', 'assets/images/logo.png');
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <img src="<?php echo asset($logoPath); ?>" alt="SIPWAY" onerror="this.style.display='none'">
        <span>SIPWAY</span>
    </div>
    <nav class="mt-2">
        <div class="nav-section">Main</div>
        <a href="<?php echo asset('admin/dashboard.php'); ?>" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        
        <div class="nav-section">Content</div>
        <a href="<?php echo asset('admin/site-content.php'); ?>" class="nav-link <?php echo $currentPage === 'site-content.php' ? 'active' : ''; ?>">
            <i class="bi bi-layout-text-window"></i> Site Content
        </a>
        <a href="<?php echo asset('admin/pages.php'); ?>" class="nav-link <?php echo $currentPage === 'pages.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i> Pages
        </a>
        <a href="<?php echo asset('admin/testimonials.php'); ?>" class="nav-link <?php echo $currentPage === 'testimonials.php' ? 'active' : ''; ?>">
            <i class="bi bi-chat-quote"></i> Testimonials
        </a>
        <a href="<?php echo asset('admin/media.php'); ?>" class="nav-link <?php echo $currentPage === 'media.php' ? 'active' : ''; ?>">
            <i class="bi bi-images"></i> Media
        </a>

        <div class="nav-section">Academic</div>
        <a href="<?php echo asset('admin/teachers.php'); ?>" class="nav-link <?php echo in_array($currentPage, ['teachers.php','teacher-add.php','teacher-edit.php']) ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Teachers
        </a>
        <a href="<?php echo asset('admin/classes.php'); ?>" class="nav-link <?php echo in_array($currentPage, ['classes.php','class-add.php','class-edit.php']) ? 'active' : ''; ?>">
            <i class="bi bi-journal-bookmark"></i> Classes
        </a>
        <a href="<?php echo asset('admin/categories.php'); ?>" class="nav-link <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="<?php echo asset('admin/subjects.php'); ?>" class="nav-link <?php echo $currentPage === 'subjects.php' ? 'active' : ''; ?>">
            <i class="bi bi-book"></i> Subjects
        </a>
        <a href="<?php echo asset('admin/schedules.php'); ?>" class="nav-link <?php echo $currentPage === 'schedules.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar3"></i> Schedules
        </a>

        <div class="nav-section">Communications</div>
        <a href="<?php echo asset('admin/contact-messages.php'); ?>" class="nav-link <?php echo $currentPage === 'contact-messages.php' ? 'active' : ''; ?>">
            <i class="bi bi-envelope"></i> Contact Messages
        </a>
        <a href="<?php echo asset('admin/inquiries.php'); ?>" class="nav-link <?php echo $currentPage === 'inquiries.php' ? 'active' : ''; ?>">
            <i class="bi bi-question-circle"></i> Inquiries
        </a>

        <div class="nav-section">System</div>
        <a href="<?php echo asset('admin/settings.php'); ?>" class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
        <a href="<?php echo asset('admin/activity-logs.php'); ?>" class="nav-link <?php echo $currentPage === 'activity-logs.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i> Activity Logs
        </a>
        <a href="<?php echo asset('admin/profile.php'); ?>" class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i> Profile
        </a>
        <a href="<?php echo asset('admin/logout.php'); ?>" class="nav-link">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</aside>
