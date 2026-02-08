<?php
/**
 * Admin - Message Management
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$db = get_db();

// Handle delete message
if (isset($_GET['delete'])) {
    $msg_id = (int)$_GET['delete'];
    $stmt = $db->prepare("
        UPDATE messages 
        SET is_deleted = TRUE, 
            deleted_by = ?, 
            deleted_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([get_current_user_id(), $msg_id]);
    set_flash('success', 'Message deleted');
    redirect('admin_messages', ['show' => $_GET['show'] ?? 'active']);
}

// Handle restore message
if (isset($_GET['restore'])) {
    $msg_id = (int)$_GET['restore'];
    $stmt = $db->prepare("
        UPDATE messages 
        SET is_deleted = FALSE, 
            deleted_by = NULL, 
            deleted_at = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$msg_id]);
    set_flash('success', 'Message restored');
    redirect('admin_messages', ['show' => 'deleted']);
}

// Handle permanent delete
if (isset($_GET['permanent_delete'])) {
    $msg_id = (int)$_GET['permanent_delete'];
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$msg_id]);
    set_flash('success', 'Message permanently deleted');
    redirect('admin_messages', ['show' => 'deleted']);
}

// Get filter
$show = $_GET['show'] ?? 'active';

// Build query based on filter
if ($show === 'deleted') {
    $where = 'WHERE m.is_deleted = TRUE';
} else {
    $where = 'WHERE m.is_deleted = FALSE';
}

$stmt = $db->query("
    SELECT m.*, u.username, s.name as sub_name, d.username as deleted_by_username
    FROM messages m
    JOIN users u ON m.user_id = u.id
    JOIN subs s ON m.sub_id = s.id
    LEFT JOIN users d ON m.deleted_by = d.id
    {$where}
    ORDER BY m.created_at DESC
    LIMIT 100
");
$messages = $stmt->fetchAll();

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=admin">Admin</a>
        <span>&gt;</span>
        <span>Message Management</span>
    </div>
</div>

<h1 class="mb-lg">Message Management</h1>

<!-- Filter -->
<div style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
    <a href="/?page=admin_messages&show=active" class="btn <?= $show === 'active' ? 'btn-primary' : '' ?>">
        Active Messages
    </a>
    <a href="/?page=admin_messages&show=deleted" class="btn <?= $show === 'deleted' ? 'btn-primary' : '' ?>">
        Deleted Messages
    </a>
</div>

<?php if (empty($messages)): ?>
    <div class="status-bar">No messages found.</div>
<?php else: ?>
    <?php foreach ($messages as $msg): ?>
        <div class="message <?= $msg['is_deleted'] ? '' : '' ?>" style="margin-bottom: 1.5rem;">
            <div class="message-header">
                <div class="message-meta">
                    <span class="message-number">#<?= $msg['id'] ?></span>
                    <span class="message-author"><?= e($msg['username']) ?></span>
                    <span class="message-timestamp"><?= time_ago($msg['created_at']) ?></span>
                    <?php if ($msg['is_deleted']): ?>
                        <span style="background: #ef4444; color: white; padding: 0.125rem 0.5rem; border-radius: 3px; font-size: 0.75rem;">
                            DELETED
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($msg['parent_id']): ?>
                <div class="message-reply-ref">
                    In reply to: <a href="#msg-<?= $msg['parent_id'] ?>">#<?= $msg['parent_id'] ?></a>
                </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.875rem;">
                <strong>Sub:</strong> <?= e($msg['sub_name']) ?>
            </div>
            
            <div class="message-subject">
                <?= e($msg['subject']) ?>
            </div>
            
            <div class="message-body">
                <?= format_message_body($msg['body']) ?>
            </div>
            
            <?php if ($msg['is_deleted']): ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); color: var(--text-tertiary); font-size: 0.875rem;">
                    <strong>Deleted by:</strong> <?= e($msg['deleted_by_username'] ?? 'Unknown') ?> | 
                    <strong>Deleted at:</strong> <?= date('Y-m-d H:i:s', strtotime($msg['deleted_at'])) ?>
                </div>
            <?php endif; ?>
            
            <div class="message-actions">
                <a href="/?page=sub&id=<?= $msg['sub_id'] ?>" class="btn btn-small">View in Sub</a>
                
                <?php if ($msg['is_deleted']): ?>
                    <a href="/?page=admin_messages&restore=<?= $msg['id'] ?>" 
                       class="btn btn-small"
                       onclick="return confirm('Restore this message?')">
                        Restore
                    </a>
                    <a href="/?page=admin_messages&permanent_delete=<?= $msg['id'] ?>" 
                       class="btn btn-small"
                       style="color: #ef4444;"
                       onclick="return confirm('PERMANENTLY delete this message? This cannot be undone!')">
                        Permanent Delete
                    </a>
                <?php else: ?>
                    <a href="/?page=admin_messages&delete=<?= $msg['id'] ?>" 
                       class="btn btn-small"
                       onclick="return confirm('Delete this message?')">
                        Delete
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div style="margin-top: 2rem; padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 0.875rem;">
    <strong>Note:</strong> Deleted messages are soft-deleted (hidden but recoverable). 
    Use "Permanent Delete" to remove them completely from the database.
    Showing last 100 messages.
</div>

<?php
$content = ob_get_clean();
$page_title = 'Message Management - Admin';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
