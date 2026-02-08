<?php
/**
 * Sub - View Messages (New Only by Default)
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

// Get new messages
$data = get_new_messages($sub_id, $user_id);
$messages = $data['messages'];
$last_read_time = $data['last_read_time'];
$new_count = count($messages);

// Update read pointer (user has now seen these messages)
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
        Showing <?= $new_count ?> new message<?= $new_count != 1 ? 's' : '' ?> 
        since <?= date('l \a\t g:ia', strtotime($last_read_time)) ?>
        <span style="margin-left: 1rem;">
            <a href="/?page=sub_all&id=<?= $sub_id ?>">Show all messages</a>
        </span>
    </div>
<?php elseif ($new_count > 0): ?>
    <div class="status-bar">
        Showing <?= $new_count ?> message<?= $new_count != 1 ? 's' : '' ?>
        <span style="margin-left: 1rem;">
            <a href="/?page=sub_all&id=<?= $sub_id ?>">Show all messages</a>
        </span>
    </div>
<?php endif; ?>

<?php if (empty($messages)): ?>
    <div class="status-bar">
        No new messages. 
        <a href="/?page=sub_all&id=<?= $sub_id ?>">View all messages</a> or 
        <a href="/?page=new_thread&sub_id=<?= $sub_id ?>">start a new thread</a>.
    </div>
<?php else: ?>
    <?php foreach ($messages as $msg): ?>
        <div class="message is-new" id="msg-<?= $msg['id'] ?>">
            <div class="message-header">
                <div class="message-meta">
                    <span class="message-number">#<?= $msg['id'] ?></span>
                    <a href="/?page=user_profile&id=<?= $msg['user_id'] ?>" class="message-author"><?= e($msg['username']) ?></a>
                    <span class="message-timestamp"><?= time_ago($msg['created_at']) ?></span>
                    <span class="message-badge">New</span>
                </div>
            </div>
            
            <?php if ($msg['parent_id']): ?>
                <div class="message-reply-ref">
                    In reply to: <a href="#msg-<?= $msg['parent_id'] ?>">#<?= $msg['parent_id'] ?></a>
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
    
    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; padding: 1.5rem; border-top: 1px solid var(--border);">
        <a href="/?page=sub_all&id=<?= $sub_id ?>" class="btn">Load Older Messages</a>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = e($sub['name']);
$active_page = 'dashboard';
require __DIR__ . '/layout.php';
?>
