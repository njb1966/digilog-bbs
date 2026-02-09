<?php
/**
 * Admin - Sub (Message Board) Management
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$db = get_db();

// Handle toggle active status
if (isset($_GET['toggle_active'])) {
    $sub_id = (int)$_GET['toggle_active'];
    $stmt = $db->prepare("UPDATE subs SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$sub_id]);
    set_flash('success', 'Sub status updated');
    redirect('admin_subs');
}

// Handle delete
if (isset($_GET['delete'])) {
    $sub_id = (int)$_GET['delete'];

    // Check for existing messages
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE sub_id = ?");
    $stmt->execute([$sub_id]);
    $message_count = $stmt->fetch()['count'];

    if ($message_count > 0) {
        set_flash('error', 'Cannot delete a sub that contains messages. Deactivate it instead, or delete all its messages first.');
    } else {
        $stmt = $db->prepare("DELETE FROM subs WHERE id = ?");
        $stmt->execute([$sub_id]);
        set_flash('success', 'Sub deleted');
    }
    redirect('admin_subs');
}

// Get all subs with message counts
$stmt = $db->query("
    SELECT s.*,
           COUNT(m.id) as message_count
    FROM subs s
    LEFT JOIN messages m ON s.id = m.sub_id AND m.is_deleted = FALSE
    GROUP BY s.id
    ORDER BY s.position ASC
");
$subs = $stmt->fetchAll();

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=admin">Admin</a>
        <span>&gt;</span>
        <span>Manage Subs</span>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
    <h1>Manage Subs</h1>
    <a href="/?page=admin_edit_sub" class="btn btn-primary">Create New Sub</a>
</div>

<div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-lg);">
    <?php if (empty($subs)): ?>
        <p class="text-secondary">No subs found.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border);">
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">ID</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Name</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Slug</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Pos</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Messages</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Status</th>
                    <th style="text-align: left; padding: 0.75rem; color: var(--text-secondary); font-weight: 500;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subs as $sub): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 0.75rem; font-family: var(--font-message); color: var(--text-tertiary);">
                            <?= $sub['id'] ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?= e($sub['name']) ?>
                            <?php if ($sub['description']): ?>
                                <div style="color: var(--text-tertiary); font-size: 0.8rem; margin-top: 0.25rem;">
                                    <?= e(mb_strimwidth($sub['description'], 0, 80, '...')) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem; font-family: var(--font-message); color: var(--text-secondary);">
                            <?= e($sub['slug']) ?>
                        </td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);">
                            <?= $sub['position'] ?>
                        </td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);">
                            <?= $sub['message_count'] ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <?php if ($sub['is_active']): ?>
                                <span style="color: #22c55e;">Active</span>
                            <?php else: ?>
                                <span style="color: #ef4444;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem;">
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="/?page=admin_edit_sub&id=<?= $sub['id'] ?>"
                                   class="btn btn-small">
                                    Edit
                                </a>
                                <a href="/?page=admin_subs&toggle_active=<?= $sub['id'] ?>"
                                   class="btn btn-small"
                                   onclick="return confirm('<?= $sub['is_active'] ? 'Deactivate' : 'Activate' ?> this sub?')">
                                    <?= $sub['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </a>
                                <a href="/?page=admin_subs&delete=<?= $sub['id'] ?>"
                                   class="btn btn-small"
                                   onclick="return confirm('Permanently delete this sub? This only works if it has no messages.')">
                                    Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Manage Subs - Admin';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
