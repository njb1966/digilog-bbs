<?php
/**
 * Reply - Reply to an existing message
 */

require_once __DIR__ . '/../messages.php';

$msg_id = (int)($_GET['msg_id'] ?? 0);
$quote = isset($_GET['quote']);
$user_id = get_current_user_id();

// Get parent message
$parent_msg = get_message($msg_id);

if (!$parent_msg) {
    set_flash('error', 'Message not found');
    redirect('dashboard');
}

// Get sub info
$sub = get_sub($parent_msg['sub_id']);

// Prepare quoted text if requested
$quoted_body = '';
if ($quote) {
    $lines = explode("\n", $parent_msg['body']);
    $quoted_lines = array_map(function($line) {
        return '> ' . $line;
    }, $lines);
    $quoted_body = implode("\n", $quoted_lines) . "\n\n";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verify_csrf_token($csrf_token)) {
        set_flash('error', 'Invalid security token. Please try again.');
    } elseif (empty($subject) || empty($body)) {
        set_flash('error', 'Subject and message are required');
    } else {
        $result = post_message($parent_msg['sub_id'], $user_id, $subject, $body, $parent_msg['id']);
        
        if ($result['success']) {
            set_flash('success', 'Reply posted successfully!');
            redirect('sub', ['id' => $parent_msg['sub_id']]);
        } else {
            set_flash('error', $result['error']);
        }
    }
}

// Auto-generate subject if empty
$default_subject = $parent_msg['subject'];
if (!preg_match('/^Re:/i', $default_subject)) {
    $default_subject = 'Re: ' . $default_subject;
}

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=dashboard">Home</a>
        <span>&gt;</span>
        <a href="/?page=sub&id=<?= $parent_msg['sub_id'] ?>"><?= e($sub['name']) ?></a>
        <span>&gt;</span>
        <span>Reply to #<?= $parent_msg['id'] ?></span>
    </div>
</div>

<div class="context-box">
    <div class="context-box-header">
        Replying to message #<?= $parent_msg['id'] ?> by <?= e($parent_msg['username']) ?>:
    </div>
    <div class="context-box-body"><?= e($parent_msg['body']) ?></div>
</div>

<form method="POST" action="/?page=reply&msg_id=<?= $msg_id ?>" style="max-width: 800px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    
    <div class="form-group">
        <label class="form-label" for="subject">Subject</label>
        <input 
            type="text" 
            id="subject" 
            name="subject"
            class="form-input" 
            required
            maxlength="255"
            value="<?= e($_POST['subject'] ?? $default_subject) ?>"
        >
    </div>
    
    <div class="form-group">
        <label class="form-label" for="body">Message</label>
        <textarea 
            id="body" 
            name="body"
            class="form-textarea" 
            placeholder="Type your reply here..."
            required
            autofocus
            style="min-height: 300px;"
        ><?= e($_POST['body'] ?? $quoted_body) ?></textarea>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Post Reply</button>
        <a href="/?page=sub&id=<?= $parent_msg['sub_id'] ?>" class="btn">Cancel</a>
        <?php if (!$quote): ?>
            <a href="/?page=reply&msg_id=<?= $msg_id ?>&quote=1">Quote Original</a>
        <?php endif; ?>
    </div>
</form>

<?php
$content = ob_get_clean();
$page_title = 'Reply to #' . $parent_msg['id'];
$active_page = 'dashboard';
require __DIR__ . '/layout.php';
?>
