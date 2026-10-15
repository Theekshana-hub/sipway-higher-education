<?php
$admin = getCurrentAdmin();
$initials = $admin ? strtoupper(substr($admin['full_name'] ?: $admin['username'], 0, 1)) : 'A';
?>
<header class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="mb-0 d-none d-md-block"><?php echo e($pageTitle ?? 'Dashboard'); ?></h5>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="<?php echo asset('index.php'); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Website">
            <i class="bi bi-box-arrow-up-right"></i> <span class="d-none d-sm-inline">View Site</span>
        </a>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark" data-bs-toggle="dropdown">
                <div class="admin-avatar"><?php echo e($initials); ?></div>
                <span class="d-none d-sm-inline"><?php echo e($admin['full_name'] ?: $admin['username'] ?? 'Admin'); ?></span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?php echo asset('admin/profile.php'); ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="<?php echo asset('admin/settings.php'); ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?php echo asset('admin/logout.php'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
