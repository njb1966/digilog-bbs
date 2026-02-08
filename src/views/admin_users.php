<?php
/**
 * Admin - User Management
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$db = get_db();
$current_user_id = get_current_user_id();

// Handle toggle active status
if (isset($_GET['toggle_active'])) {
    $user_id = (int)$_GET['toggle_active'];
    
    // Don't allow disabling yourself
    if ($user_id === $current_user_id) {
        set_flash('error', 'You cannot disable your own account');
    } else {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$user_id]);
        set_flash('success', 'User status updated');
    }
    redirect('admin_users');
}

// Handle toggle admin status
if (isset($_GET['toggle_admin'])) {
    $user_id = (int)$_GET['toggle_admin'];
    
    // Don't allow removing your own admin
    if ($user_id === $current_user_id) {
        set_flash('error', 'You cannot remove your own admin privileges');
    } else {
        $stmt = $db->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
        $stmt->execute([$user_id]);
        set_flash('success', 'Admin status updated');
    }
    redirect('admin_users');
}

// Get all users
$stmt = $db->query("
    SELECT u.*, 
           COUNT(DISTINCT m.id) as message_count,
           MAX(m.created_at) as last_post
    FROM users u
    LEFT JOIN messages m ON u.id = m.user_id AND m.is_deleted = FALSE
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=admin">Admin</a>
        <span>&gt;</span>
        <span>User Management</span>
    </div>
</div>

<h1 class="mb-lg">User Management</h1>

<div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg);">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border);">
                <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">ID</th>
                <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Username</th>
                <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Email</th>
                <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Joined</th>
                <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Posts</th>
                <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 0.75rem; font-family: var(--font-message); color: var(--text-tertiary);">
                        <?= $user['id'] ?>
                    </td>
                    <td style="padding: 0.75rem;">
                        <?= e($user['username']) ?>
                        <?php if ($user['is_admin']): ?>
                            <span style="color: var(--accent); font-size: 0.75rem;">(Admin)</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem; color: var(--text-secondary);">
                        <?= e($user['email']) ?>
                    </td>
                    <td style="padding: 0.75rem; color: var(--text-secondary);">
                        <?= date('M j, Y', strtotime($user['created_at'])) ?>
                    </td>
                    <td style="padding: 0.75rem; color: var(--text-secondary);">
                        <?= $user['message_count'] ?>
                    </td>
                    <td style="padding: 0.75rem;">
                        <?php if ($user['is_active']): ?>
                            <span style="color: #22c55e;">Active</span>
                        <?php else: ?>
                            <span style="color: #ef4444;">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php if ($user['id'] !== $current_user_id): ?>
                                <a href="/?page=admin_edit_user&user_id=<?= $user['id'] ?>"
                                   class="btn btn-small">
                                    Edit
                                </a>
                                <a href="/?page=admin_reset_password&user_id=<?= $user['id'] ?>"
                                   class="btn btn-small">
                                    Reset Password
                                </a>
                                <a href="/?page=admin_users&toggle_active=<?= $user['id'] ?>" 
                                   class="btn btn-small"
                                   onclick="return confirm('<?= $user['is_active'] ? 'Disable' : 'Enable' ?> this user?')">
                                    <?= $user['is_active'] ? 'Disable' : 'Enable' ?>
                                </a>
                                <a href="/?page=admin_users&toggle_admin=<?= $user['id'] ?>" 
                                   class="btn btn-small"
                                   onclick="return confirm('<?= $user['is_admin'] ? 'Remove admin from' : 'Make admin' ?> this user?')">
                                    <?= $user['is_admin'] ? 'Remove Admin' : 'Make Admin' ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-tertiary); font-size: 0.875rem;">(You)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
$page_title = 'User Management - Admin';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
