<?php
/**
 * Admin - Edit User Profile
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$user_id = (int)($_GET['user_id'] ?? 0);

$db = get_db();

// Get user info
$stmt = $db->prepare("SELECT id, username, email, bio, location, email_verified FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    set_flash('error', 'User not found');
    redirect('admin_users');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $email_verified = isset($_POST['email_verified']);

    if (!verify_csrf_token($csrf_token)) {
        set_flash('error', 'Invalid security token. Please try again.');
    } elseif (empty($username) || empty($email)) {
        set_flash('error', 'Username and email are required');
    } else {
        $result = admin_update_user($user_id, $username, $email, $bio, $location, $email_verified);

        if ($result['success']) {
            set_flash('success', 'Profile updated for ' . e($username));
            redirect('admin_users');
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
        <a href="/?page=admin">Admin</a>
        <span>&gt;</span>
        <a href="/?page=admin_users">User Management</a>
        <span>&gt;</span>
        <span>Edit User</span>
    </div>
</div>

<h1 class="mb-lg">Edit User: <?= e($target_user['username']) ?></h1>

<form method="POST" action="/?page=admin_edit_user&user_id=<?= $user_id ?>" style="max-width: 600px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

    <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            class="form-input"
            required
            pattern="[a-zA-Z0-9_-]{3,50}"
            title="3-50 characters, letters, numbers, underscore, and hyphen only"
            value="<?= e($_POST['username'] ?? $target_user['username']) ?>"
        >
        <small class="text-tertiary">3-50 characters, letters, numbers, underscore, and hyphen only</small>
    </div>

    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            class="form-input"
            required
            value="<?= e($_POST['email'] ?? $target_user['email']) ?>"
        >
    </div>

    <div class="form-group">
        <label class="form-label" for="location">Location</label>
        <input
            type="text"
            id="location"
            name="location"
            class="form-input"
            maxlength="100"
            value="<?= e($_POST['location'] ?? $target_user['location'] ?? '') ?>"
        >
    </div>

    <div class="form-group">
        <label class="form-label" for="bio">Bio</label>
        <textarea
            id="bio"
            name="bio"
            class="form-textarea"
            maxlength="500"
            style="min-height: 120px;"
        ><?= e($_POST['bio'] ?? $target_user['bio'] ?? '') ?></textarea>
        <small class="text-tertiary">500 characters max</small>
    </div>

    <div style="padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-sm); margin-bottom: var(--space-lg);">
        <label style="display: flex; align-items: center; cursor: pointer;">
            <input type="checkbox" name="email_verified" style="margin-right: 0.5rem;"
                <?= (isset($_POST['email_verified']) || (!isset($_POST['csrf_token']) && $target_user['email_verified'])) ? 'checked' : '' ?>
            >
            <span style="color: var(--text-secondary);">Email Verified</span>
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/?page=admin_users" class="btn">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
$page_title = 'Edit User - Admin';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
