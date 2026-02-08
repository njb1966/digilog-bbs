<?php
/**
 * Admin - Reset User Password
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$user_id = (int)($_GET['user_id'] ?? 0);

$db = get_db();

// Get user info
$stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    set_flash('error', 'User not found');
    redirect('admin_users');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verify_csrf_token($csrf_token)) {
        set_flash('error', 'Invalid security token. Please try again.');
    } elseif (empty($new_password) || empty($confirm_password)) {
        set_flash('error', 'Both password fields are required');
    } elseif ($new_password !== $confirm_password) {
        set_flash('error', 'Passwords do not match');
    } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
        set_flash('error', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
    } else {
        // Update password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        
        set_flash('success', 'Password reset successfully for ' . e($target_user['username']));
        redirect('admin_users');
    }
}

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=admin">Admin</a>
        <span>&gt;</span>
        <a href="/?page=admin_users">User Management</a>
        <span>&gt;</span>
        <span>Reset Password</span>
    </div>
</div>

<h1 class="mb-lg">Reset Password for <?= e($target_user['username']) ?></h1>

<div style="background: var(--bg-tertiary); border-left: 3px solid var(--accent); padding: var(--space-lg); margin-bottom: var(--space-xl); border-radius: var(--radius-sm);">
    <p style="margin-bottom: 0.5rem;"><strong>User ID:</strong> <?= $target_user['id'] ?></p>
    <p style="margin-bottom: 0.5rem;"><strong>Username:</strong> <?= e($target_user['username']) ?></p>
    <p><strong>Email:</strong> <?= e($target_user['email']) ?></p>
</div>

<form method="POST" action="/?page=admin_reset_password&user_id=<?= $user_id ?>" style="max-width: 500px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    
    <div class="form-group">
        <label class="form-label" for="new_password">New Password</label>
        <input 
            type="password" 
            id="new_password" 
            name="new_password"
            class="form-input" 
            placeholder="Enter new password"
            required
            autofocus
            minlength="<?= PASSWORD_MIN_LENGTH ?>"
        >
        <small class="text-tertiary">Minimum <?= PASSWORD_MIN_LENGTH ?> characters</small>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm New Password</label>
        <input 
            type="password" 
            id="confirm_password" 
            name="confirm_password"
            class="form-input" 
            placeholder="Re-enter new password"
            required
        >
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Reset Password</button>
        <a href="/?page=admin_users" class="btn">Cancel</a>
    </div>
</form>

<div style="margin-top: 2rem; padding: 1rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 0.875rem; max-width: 500px;">
    <strong>Note:</strong> The user will not be notified of this password change. 
    You should communicate the new password to them securely through another channel.
</div>

<?php
$content = ob_get_clean();
$page_title = 'Reset Password - Admin';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
