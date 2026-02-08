<?php
/**
 * Change Password (User)
 */

require_login();

$user_id = get_current_user_id();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verify_csrf_token($csrf_token)) {
        set_flash('error', 'Invalid security token. Please try again.');
    } elseif (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        set_flash('error', 'All fields are required');
    } elseif ($new_password !== $confirm_password) {
        set_flash('error', 'New passwords do not match');
    } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
        set_flash('error', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
    } else {
        $db = get_db();
        
        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password_hash'])) {
            set_flash('error', 'Current password is incorrect');
        } else {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);
            
            set_flash('success', 'Password changed successfully!');
            redirect('dashboard');
        }
    }
}

// Start output buffering for content
ob_start();
?>

<h1 class="mb-lg">Change Password</h1>

<form method="POST" action="/?page=change_password" style="max-width: 500px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    
    <div class="form-group">
        <label class="form-label" for="current_password">Current Password</label>
        <input 
            type="password" 
            id="current_password" 
            name="current_password"
            class="form-input" 
            placeholder="Enter your current password"
            required
            autofocus
        >
    </div>
    
    <div class="form-group">
        <label class="form-label" for="new_password">New Password</label>
        <input 
            type="password" 
            id="new_password" 
            name="new_password"
            class="form-input" 
            placeholder="Enter new password"
            required
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
        <button type="submit" class="btn btn-primary">Change Password</button>
        <a href="/?page=dashboard" class="btn">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
$page_title = 'Change Password';
$active_page = 'dashboard';
require __DIR__ . '/layout.php';
?>
