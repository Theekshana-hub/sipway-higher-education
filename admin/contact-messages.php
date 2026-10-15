<?php
define('SIPWAY_APP', true);
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
$pdo = getDB();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] ?? 'new';
        $notes = trim($_POST['internal_notes'] ?? '');
        if (in_array($status, ['new','contacted','in_progress','resolved'])) {
            $pdo->prepare("UPDATE contact_messages SET status = ?, internal_notes = ? WHERE id = ?")->execute([$status, $notes, $id]);
            logActivity('message_update', "Updated message #$id to $status");
            redirect(BASE_URL . '/admin/contact-messages.php', 'Message updated.');
        }
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([(int)$_POST['delete_id']]);
        redirect(BASE_URL . '/admin/contact-messages.php', 'Message deleted.');
    }
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="contact-messages.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Email','Phone','Subject','Message','Status','Date']);
    $rows = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    while ($r = $rows->fetch()) {
        fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['phone'],$r['subject'],$r['message'],$r['status'],$r['created_at']]);
    }
    fclose($out);
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = ['1=1']; $params = [];
if ($statusFilter) { $where[] = 'status = ?'; $params[] = $statusFilter; }
if ($search) { $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
$wc = implode(' AND ', $where);
$page = max(1,(int)($_GET['page']??1));
$perPage = 20;
$total = (int)$pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE $wc")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE $wc")->execute($params) : 0;
// Fix count
$cs = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE $wc");
$cs->execute($params);
$total = (int)$cs->fetchColumn();
$pagination = paginate($total, $perPage, $page);
$stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE $wc ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$messages = $stmt->fetchAll();

$pageTitle = 'Contact Messages';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-4">
    <h4>Contact Messages</h4>
    <a href="?export=csv" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> Export CSV</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" class="form-control" value="<?php echo e($search); ?>" placeholder="Search..."></div>
    <div class="col-md-3"><select name="status" class="form-select"><option value="">All</option>
        <?php foreach (['new','contacted','in_progress','resolved'] as $s): ?><option value="<?php echo $s; ?>" <?php echo $statusFilter===$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form>
<div class="admin-card card">
<table class="table admin-table mb-0">
<thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php if (empty($messages)): ?><tr><td colspan="6" class="text-center text-muted">No messages</td></tr>
<?php else: foreach ($messages as $m): ?>
<tr>
<td><?php echo e($m['name']); ?></td>
<td><?php echo e($m['email']); ?></td>
<td><?php echo e(truncate($m['subject']?:'—',40)); ?></td>
<td><span class="badge badge-status-<?php echo e($m['status']); ?>"><?php echo e($m['status']); ?></span></td>
<td class="small"><?php echo formatDate($m['created_at'],'d M Y H:i'); ?></td>
<td>
<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#msg<?php echo $m['id']; ?>">View</button>
<form method="POST" class="d-inline" onsubmit="return confirm('Delete?');"><?php echo csrfField(); ?><input type="hidden" name="delete_id" value="<?php echo $m['id']; ?>"><button class="btn btn-sm btn-outline-danger">Del</button></form>
</td>
</tr>
<!-- Modal -->
<div class="modal fade" id="msg<?php echo $m['id']; ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Message from <?php echo e($m['name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<p><strong>Email:</strong> <?php echo e($m['email']); ?> <?php if ($m['phone']): ?>| <strong>Phone:</strong> <?php echo e($m['phone']); endif; ?></p>
<p><strong>Subject:</strong> <?php echo e($m['subject']?:'—'); ?></p>
<p><?php echo nl2br(e($m['message'])); ?></p>
<form method="POST"><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $m['id']; ?>"><input type="hidden" name="update_status" value="1">
<div class="mb-2"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['new','contacted','in_progress','resolved'] as $s): ?><option value="<?php echo $s; ?>" <?php echo $m['status']===$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Internal Notes</label><textarea name="internal_notes" class="form-control" rows="2"><?php echo e($m['internal_notes']); ?></textarea></div>
<button type="submit" class="btn btn-admin btn-sm">Update</button>
</form>
</div></div></div></div>
<?php endforeach; endif; ?>
</tbody></table>
</div>
<?php $baseUrl = asset('admin/contact-messages.php'); include __DIR__ . '/../includes/pagination.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
