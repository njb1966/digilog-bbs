<?php
/**
 * Admin - Manage Contact Messages
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$db = get_db();

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $contact_id = (int)$_GET['mark_read'];
    $stmt = $db->prepare("UPDATE contact_messages SET is_read = TRUE WHERE id = ?");
    $stmt->execute([$contact_id]);
    set_flash('success', 'Message marked as read');
    redirect('admin_contacts');
}

// Handle delete
if (isset($_GET['delete'])) {
    $contact_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$contact_id]);
    set_flash('success', 'Contact message deleted');
    redirect('admin_contacts');
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Build query
$where = '';
if ($filter === 'unread') {
    $where = 'WHERE is_read = FALSE';
} elseif ($filter === 'read') {
    $where = 'WHERE is_read = TRUE';
}

$stmt = $db->query("
    SELECT * FROM contact_messages 
    {$where}
    ORDER BY created_at DESC
");
$contacts = $stmt->fetchAll();

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=admin">Admin</a>
        <span>&gt;</span>
        <span>Contact Messages</span>
    </div>
</div>

<h1 class="mb-lg">Contact Messages</h1>

<!-- Filter -->
<div style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
    <a href="/?page=admin_contacts&filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : '' ?>">
        All
    </a>
    <a href="/?page=admin_contacts&filter=unread" class="btn <?= $filter === 'unread' ? 'btn-primary' : '' ?>">
        Unread
    </a>
    <a href="/?page=admin_contacts&filter=read" class="btn <?= $filter === 'read' ? 'btn-primary' : '' ?>">
        Read
    </a>
</div>

<?php if (empty($contacts)): ?>
    <div class="status-bar">No contact messages found.</div>
<?php else: ?>
    <?php foreach ($contacts as $contact): ?>
        <div class="message <?= !$contact['is_read'] ? 'is-new' : '' ?>" style="margin-bottom: 1.5rem;">
            <div class="message-header">
                <div class="message-meta">
                    <span class="message-number">#<?= $contact['id'] ?></span>
                    <span class="message-author"><?= e($contact['name']) ?></span>
                    <span class="message-timestamp"><?= time_ago($contact['created_at']) ?></span>
                    <?php if (!$contact['is_read']): ?>
                        <span class="message-badge">Unread</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-bottom: 0.5rem;">
                <strong>Email:</strong> <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a>
            </div>
            
            <div class="message-subject">
                <?= e($contact['subject']) ?>
            </div>
            
            <div class="message-body">
                <?= e($contact['message']) ?>
            </div>
            
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); color: var(--text-tertiary); font-size: 0.875rem;">
                <strong>IP:</strong> <?= e($contact['ip_address']) ?> | 
                <strong>Sent:</strong> <?= date('Y-m-d H:i:s', strtotime($contact['created_at'])) ?>
            </div>
            
            <div class="message-actions">
                <?php if (!$contact['is_read']): ?>
                    <a href="/?page=admin_contacts&mark_read=<?= $contact['id'] ?>" class="btn btn-small">Mark as Read</a>
                <?php endif; ?>
                <a href="mailto:<?= e($contact['email']) ?>?subject=Re: <?= urlencode($contact['subject']) ?>" class="btn btn-small">Reply via Email</a>
                <a href="/?page=admin_contacts&delete=<?= $contact['id'] ?>" 
                   class="btn btn-small" 
                   onclick="return confirm('Delete this message?')">Delete</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Contact Messages - Admin';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
