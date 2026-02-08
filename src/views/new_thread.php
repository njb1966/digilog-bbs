<?php
/**
 * New Thread - Post a new message in a sub
 */

require_once __DIR__ . '/../messages.php';

$sub_id = (int)($_GET['sub_id'] ?? 0);
$user_id = get_current_user_id();

// Get sub info
$sub = get_sub($sub_id);

if (!$sub) {
    set_flash('error', 'Message board not found');
    redirect('dashboard');
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
        $result = post_message($sub_id, $user_id, $subject, $body);
        
        if ($result['success']) {
            set_flash('success', 'Message posted successfully!');
            redirect('sub', ['id' => $sub_id]);
        } else {
            set_flash('error', $result['error']);
        }
    }
}

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=dashboard">Home</a>
        <span>&gt;</span>
        <a href="/?page=sub&id=<?= $sub_id ?>"><?= e($sub['name']) ?></a>
        <span>&gt;</span>
        <span>New Thread</span>
    </div>
</div>

<h1 class="mb-lg">New Thread in <?= e($sub['name']) ?></h1>

<form method="POST" action="/?page=new_thread&sub_id=<?= $sub_id ?>" style="max-width: 800px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    
    <div class="form-group">
        <label class="form-label" for="subject">Subject</label>
        <input 
            type="text" 
            id="subject" 
            name="subject"
            class="form-input" 
            placeholder="Enter a descriptive subject"
            required
            autofocus
            maxlength="255"
            value="<?= e($_POST['subject'] ?? '') ?>"
        >
    </div>
    
    <div class="form-group">
        <label class="form-label" for="body">Message</label>
        <textarea 
            id="body" 
            name="body"
            class="form-textarea" 
            placeholder="Type your message here..."
            required
            style="min-height: 300px;"
        ><?= e($_POST['body'] ?? '') ?></textarea>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Post Message</button>
        <a href="/?page=sub&id=<?= $sub_id ?>" class="btn">Cancel</a>
    </div>
</form>

<div style="margin-top: 2rem; padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 0.875rem; max-width: 800px;">
    <strong>Posting Guidelines:</strong><br>
    • Keep it civil and respectful<br>
    • Stay on topic for this board<br>
    • No spam or excessive self-promotion<br>
    • Plain text formatting - monospace display
</div>

<?php
$content = ob_get_clean();
$page_title = 'New Thread - ' . e($sub['name']);
$active_page = 'dashboard';
require __DIR__ . '/layout.php';
?>
