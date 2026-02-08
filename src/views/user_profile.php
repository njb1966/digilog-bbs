<?php
/**
 * User Profile - Public View
 */

$profile_id = (int)($_GET['id'] ?? 0);

if (!$profile_id) {
    set_flash('error', 'User not found');
    redirect('dashboard');
}

$profile = get_user_profile($profile_id);

if (!$profile) {
    set_flash('error', 'User not found');
    redirect('dashboard');
}

$is_own_profile = (get_current_user_id() === $profile['id']);

ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=dashboard">Home</a>
        <span>&gt;</span>
        <span><?= e($profile['username']) ?></span>
    </div>
    <?php if ($is_own_profile): ?>
        <a href="/?page=edit_profile" class="btn btn-small">Edit Profile</a>
    <?php endif; ?>
</div>

<div class="profile-card">
    <div class="profile-header">
        <h1 class="profile-username"><?= e($profile['username']) ?></h1>
        <?php if (!empty($profile['location'])): ?>
            <span class="profile-location"><?= e($profile['location']) ?></span>
        <?php endif; ?>
    </div>

    <?php if (!empty($profile['bio'])): ?>
        <div class="profile-bio"><?= e($profile['bio']) ?></div>
    <?php endif; ?>

    <div class="profile-stats">
        <div class="profile-stat">
            <span class="profile-stat-value"><?= $profile['post_count'] ?></span>
            <span class="profile-stat-label">Posts</span>
        </div>
        <div class="profile-stat">
            <span class="profile-stat-value"><?= date('M j, Y', strtotime($profile['created_at'])) ?></span>
            <span class="profile-stat-label">Member Since</span>
        </div>
        <?php if ($profile['last_login']): ?>
        <div class="profile-stat">
            <span class="profile-stat-value"><?= time_ago($profile['last_login']) ?></span>
            <span class="profile-stat-label">Last Login</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = e($profile['username']) . "'s Profile";
$active_page = '';
require __DIR__ . '/layout.php';
?>
