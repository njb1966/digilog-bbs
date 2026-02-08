<?php
/**
 * Admin Dashboard
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$db = get_db();

// Get statistics
$stats = [];

// Total users
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = TRUE");
$stats['total_users'] = $stmt->fetch()['count'];

// Total messages
$stmt = $db->query("SELECT COUNT(*) as count FROM messages WHERE is_deleted = FALSE");
$stats['total_messages'] = $stmt->fetch()['count'];

// Deleted messages
$stmt = $db->query("SELECT COUNT(*) as count FROM messages WHERE is_deleted = TRUE");
$stats['deleted_messages'] = $stmt->fetch()['count'];

// Unread contact messages
$stmt = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = FALSE");
$stats['unread_contacts'] = $stmt->fetch()['count'];

// Recent users (last 7 days)
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['recent_users'] = $stmt->fetch()['count'];

// Recent messages (last 24 hours)
$stmt = $db->query("SELECT COUNT(*) as count FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_deleted = FALSE");
$stats['recent_messages'] = $stmt->fetch()['count'];

// Get recent activity
$stmt = $db->query("
    SELECT m.id, m.subject, m.created_at, u.username, s.name as sub_name, m.is_deleted
    FROM messages m
    JOIN users u ON m.user_id = u.id
    JOIN subs s ON m.sub_id = s.id
    ORDER BY m.created_at DESC
    LIMIT 10
");
$recent_activity = $stmt->fetchAll();

// Start output buffering for content
ob_start();
?>

<h1 class="mb-lg">Admin Dashboard</h1>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Stats Cards -->
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg);">
        <div style="color: var(--text-tertiary); font-size: 0.875rem; margin-bottom: 0.5rem;">Total Users</div>
        <div style="font-size: 2rem; font-weight: 600; color: var(--accent);"><?= $stats['total_users'] ?></div>
        <div style="color: var(--text-tertiary); font-size: 0.875rem; margin-top: 0.5rem;">
            +<?= $stats['recent_users'] ?> this week
        </div>
    </div>
    
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg);">
        <div style="color: var(--text-tertiary); font-size: 0.875rem; margin-bottom: 0.5rem;">Total Messages</div>
        <div style="font-size: 2rem; font-weight: 600; color: var(--accent);"><?= $stats['total_messages'] ?></div>
        <div style="color: var(--text-tertiary); font-size: 0.875rem; margin-top: 0.5rem;">
            +<?= $stats['recent_messages'] ?> last 24h
        </div>
    </div>
    
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg);">
        <div style="color: var(--text-tertiary); font-size: 0.875rem; margin-bottom: 0.5rem;">Unread Contacts</div>
        <div style="font-size: 2rem; font-weight: 600; color: <?= $stats['unread_contacts'] > 0 ? '#ef4444' : 'var(--accent)' ?>;">
            <?= $stats['unread_contacts'] ?>
        </div>
        <div style="margin-top: 0.5rem;">
            <a href="/?page=admin_contacts">View All</a>
        </div>
    </div>
    
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg);">
        <div style="color: var(--text-tertiary); font-size: 0.875rem; margin-bottom: 0.5rem;">Deleted Messages</div>
        <div style="font-size: 2rem; font-weight: 600; color: var(--text-secondary);"><?= $stats['deleted_messages'] ?></div>
        <div style="margin-top: 0.5rem;">
            <a href="/?page=admin_messages&show=deleted">View Deleted</a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg); margin-bottom: 2rem;">
    <h2 style="margin-bottom: 1rem;">Quick Actions</h2>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="/?page=admin_contacts" class="btn">Manage Contacts</a>
        <a href="/?page=admin_users" class="btn">Manage Users</a>
        <a href="/?page=admin_messages" class="btn">Manage Messages</a>
        <a href="/?page=dashboard" class="btn">Back to Dashboard</a>
    </div>
</div>

<!-- Recent Activity -->
<div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg);">
    <h2 style="margin-bottom: 1rem;">Recent Activity</h2>
    
    <?php if (empty($recent_activity)): ?>
        <p class="text-secondary">No recent activity.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border);">
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">ID</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Subject</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">User</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Sub</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Time</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_activity as $activity): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 0.75rem; font-family: var(--font-message); color: var(--text-tertiary);">#<?= $activity['id'] ?></td>
                        <td style="padding: 0.75rem;">
                            <a href="/?page=sub&id=<?= $activity['id'] ?>"><?= e($activity['subject']) ?></a>
                        </td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);"><?= e($activity['username']) ?></td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);"><?= e($activity['sub_name']) ?></td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);"><?= time_ago($activity['created_at']) ?></td>
                        <td style="padding: 0.75rem;">
                            <?php if ($activity['is_deleted']): ?>
                                <span style="color: #ef4444;">Deleted</span>
                            <?php else: ?>
                                <span style="color: #22c55e;">Active</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Admin Dashboard';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
