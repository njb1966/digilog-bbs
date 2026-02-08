<?php
/**
 * Sub - View All Messages (with older context)
 */

require_once __DIR__ . '/../messages.php';

$sub_id = (int)($_GET['id'] ?? 0);
$user_id = get_current_user_id();

// Get sub info
$sub = get_sub($sub_id);

if (!$sub) {
    set_flash('error', 'Message board not found');
    redirect('dashboard');
}

// Get all messages (with new/old indicator)
$data = get_messages($sub_id, $user_id);
$messages = $data['messages'];
$last_read_id = $data['last_read_id'];
$last_read_time = $data['last_read_time'];

// Count new messages
$new_count = 0;
foreach ($messages as $msg) {
    if ($msg['is_new']) {
        $new_count++;
    }
}

// Update read pointer
if (!empty($messages)) {
    update_read_pointer($user_id, $sub_id);
}

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=dashboard">Home</a>
        <span>&gt;</span>
        <span><?= e($sub['name']) ?></span>
    </div>
    <a href="/?page=new_thread&sub_id=<?= $sub_id ?>" class="btn btn-small btn-primary">New Thread</a>
</div>

<?php if ($new_count > 0 && $last_read_time): ?>
    <div class="status-bar">
        Showing messages #<?= $messages[0]['id'] ?? 0 ?>-<?= end($messages)['id'] ?? 0 ?> 
        (<?= $new_count ?> new)
        <span style="margin-left: 1rem;">
            <a href="#" onclick="document.getElementById('first-unread').scrollIntoView({behavior: 'smooth', block: 'center'}); return false;">
                Jump to first unread
            </a>
            |
            <a href="/?page=sub&id=<?= $sub_id ?>">Show new only</a>
        </span>
    </div>
<?php endif; ?>

<?php if (empty($messages)): ?>
    <div class="status-bar">
        No messages yet. 
        <a href="/?page=new_thread&sub_id=<?= $sub_id ?>">Start a new thread</a>!
    </div>
<?php else: ?>
    <?php 
    $first_new_found = false;
    foreach ($messages as $msg): 
        $is_new = (bool)$msg['is_new'];
        
        // Add separator before first new message
        if ($is_new && !$first_new_found && $last_read_time) {
            $first_new_found = true;
            ?>
            <div id="first-unread" style="display: flex; align-items: center; margin: 2rem 0; color: var(--text-tertiary); font-size: 0.875rem;">
                <div style="flex: 1; height: 1px; background: var(--accent);"></div>
                <div style="padding: 0 1rem;">Last read: <?= date('l \a\t g:ia', strtotime($last_read_time)) ?></div>
                <div style="flex: 1; height: 1px; background: var(--accent);"></div>
            </div>
            <?php
        }
    ?>
        <div class="message <?= $is_new ? 'is-new' : '' ?>" id="msg-<?= $msg['id'] ?>">
            <div class="message-header">
                <div class="message-meta">
                    <span class="message-number">#<?= $msg['id'] ?></span>
                    <a href="/?page=user_profile&id=<?= $msg['user_id'] ?>" class="message-author"><?= e($msg['username']) ?></a>
                    <span class="message-timestamp"><?= time_ago($msg['created_at']) ?></span>
                    <?php if ($is_new): ?>
                        <span class="message-badge">New</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($msg['parent_id']): ?>
                <div class="message-reply-ref">
                    In reply to: <a href="#msg-<?= $msg['parent_id'] ?>">↑ #<?= $msg['parent_id'] ?></a>
                </div>
            <?php endif; ?>
            
            <div class="message-subject">
                <?= e($msg['subject']) ?>
            </div>
            
            <div class="message-body"><?= format_message_body($msg['body']) ?></div>
            
            <div class="message-actions">
                <a href="/?page=reply&msg_id=<?= $msg['id'] ?>" class="btn btn-small">Reply</a>
                <a href="/?page=reply&msg_id=<?= $msg['id'] ?>&quote=1" class="btn btn-small">Quote</a>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($messages) >= MESSAGES_PER_PAGE): ?>
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; padding: 1.5rem; border-top: 1px solid var(--border);">
            <span class="text-secondary">Showing last <?= MESSAGES_PER_PAGE ?> messages</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = e($sub['name']);
$active_page = 'dashboard';
require __DIR__ . '/layout.php';
?>
