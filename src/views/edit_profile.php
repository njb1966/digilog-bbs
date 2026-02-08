<?php
/**
 * Edit Profile - Update bio and location
 */

require_login();

$user_id = get_current_user_id();
$profile = get_user_profile($user_id);

if (!$profile) {
    set_flash('error', 'Profile not found');
    redirect('dashboard');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if (!verify_csrf_token($csrf_token)) {
        set_flash('error', 'Invalid security token. Please try again.');
    } elseif (strlen($bio) > 500) {
        set_flash('error', 'Bio must be 500 characters or less');
    } elseif (strlen($location) > 100) {
        set_flash('error', 'Location must be 100 characters or less');
    } else {
        update_user_profile($user_id, $bio, $location);
        set_flash('success', 'Profile updated!');
        redirect('user_profile', ['id' => $user_id]);
    }
}

ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=dashboard">Home</a>
        <span>&gt;</span>
        <a href="/?page=user_profile&id=<?= $user_id ?>"><?= e($profile['username']) ?></a>
        <span>&gt;</span>
        <span>Edit Profile</span>
    </div>
</div>

<h1 class="mb-lg">Edit Profile</h1>

<form method="POST" action="/?page=edit_profile" style="max-width: 600px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

    <div class="form-group">
        <label class="form-label" for="location">Location</label>
        <input
            type="text"
            id="location"
            name="location"
            class="form-input"
            placeholder="e.g. Pacific Northwest, Cyberspace, etc."
            maxlength="100"
            value="<?= e($_POST['location'] ?? $profile['location'] ?? '') ?>"
        >
    </div>

    <div class="form-group">
        <label class="form-label" for="bio">Bio</label>
        <textarea
            id="bio"
            name="bio"
            class="form-textarea"
            placeholder="Tell us a bit about yourself..."
            maxlength="500"
            style="min-height: 150px;"
        ><?= e($_POST['bio'] ?? $profile['bio'] ?? '') ?></textarea>
        <div style="color: var(--text-tertiary); font-size: 0.8rem; margin-top: 0.25rem;">500 characters max</div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Profile</button>
        <a href="/?page=user_profile&id=<?= $user_id ?>" class="btn">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
$page_title = 'Edit Profile';
$active_page = '';
require __DIR__ . '/layout.php';
?>
