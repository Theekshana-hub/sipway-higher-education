<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$pdo = getDB();

$stats = [
    'teachers' => (int)$pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'classes' => (int)$pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn(),
    'published_classes' => (int)$pdo->query("SELECT COUNT(*) FROM classes WHERE status = 'published'")->fetchColumn(),
    'subjects' => (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
    'categories' => (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'messages' => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn(),
    'new_messages' => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
    'testimonials' => (int)$pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'active'")->fetchColumn(),
];

$recentMessages = $pdo->query("SELECT id, name, email, subject, status, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentActivity = $pdo->query("SELECT l.*, a.username FROM admin_activity_logs l LEFT JOIN admins a ON l.admin_id = a.id ORDER BY l.created_at DESC LIMIT 8")->fetchAll();
$recentClasses = $pdo->query("SELECT id, title, status, created_at FROM classes ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-red"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-value"><?php echo $stats['teachers']; ?></div>
                <div class="stat-label">Teachers</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-dark"><i class="bi bi-journal-bookmark"></i></div>
            <div>
                <div class="stat-value"><?php echo $stats['classes']; ?></div>
                <div class="stat-label">Classes (<?php echo $stats['published_classes']; ?> published)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-blue"><i class="bi bi-envelope"></i></div>
            <div>
                <div class="stat-value"><?php echo $stats['messages']; ?></div>
                <div class="stat-label">Messages (<?php echo $stats['new_messages']; ?> new)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon bg-green"><i class="bi bi-book"></i></div>
            <div>
                <div class="stat-value"><?php echo $stats['subjects']; ?></div>
                <div class="stat-label">Subjects / <?php echo $stats['categories']; ?> Categories</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-card card">
            <div class="card-header d-flex justify-content-between">
                <span>Recent Contact Messages</span>
                <a href="<?php echo asset('admin/contact-messages.php'); ?>" class="small">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Name</th><th>Subject</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php if (empty($recentMessages)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No messages yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentMessages as $m): ?>
                                    <tr>
                                        <td><?php echo e($m['name']); ?></td>
                                        <td><?php echo e(truncate($m['subject'] ?: '—', 30)); ?></td>
                                        <td><span class="badge badge-status-<?php echo e($m['status']); ?>"><?php echo e($m['status']); ?></span></td>
                                        <td class="small"><?php echo formatDate($m['created_at'], 'd M Y'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card card">
            <div class="card-header">Recent Activity</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentActivity)): ?>
                        <li class="list-group-item text-muted">No activity yet</li>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $a): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <div>
                                    <strong><?php echo e($a['username'] ?? 'System'); ?></strong>
                                    <span class="text-muted">— <?php echo e($a['action']); ?></span>
                                    <?php if ($a['details']): ?><br><small class="text-muted"><?php echo e(truncate($a['details'], 50)); ?></small><?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo formatDate($a['created_at'], 'd M H:i'); ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="admin-card card">
            <div class="card-header d-flex justify-content-between">
                <span>Recent Classes</span>
                <a href="<?php echo asset('admin/classes.php'); ?>" class="small">Manage Classes</a>
            </div>
            <div class="card-body p-0">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Title</th><th>Status</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($recentClasses as $c): ?>
                            <tr>
                                <td><?php echo e($c['title']); ?></td>
                                <td><span class="badge badge-status-<?php echo e($c['status']); ?>"><?php echo e($c['status']); ?></span></td>
                                <td><?php echo formatDate($c['created_at']); ?></td>
                                <td><a href="<?php echo asset('admin/class-edit.php?id=' . $c['id']); ?>" class="btn btn-sm btn-outline-secondary">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentClasses)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No classes yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
