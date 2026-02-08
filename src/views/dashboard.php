<?php
/**
 * Dashboard - Message Board Listing
 */

require_once __DIR__ . '/../config.php';

$user_id = get_current_user_id();
$db = get_db();

// Get all active subs
$stmt = $db->query("
    SELECT * FROM subs 
    WHERE is_active = TRUE 
    ORDER BY position ASC
");
$subs = $stmt->fetchAll();

// For each sub, get new message count and last message info
$subs_data = [];
foreach ($subs as $sub) {
    // Get new message count
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_count
        FROM messages m
        LEFT JOIN read_pointers rp ON rp.sub_id = m.sub_id AND rp.user_id = ?
        WHERE m.sub_id = ? 
        AND m.id > COALESCE(rp.last_read_message_id, 0)
        AND m.is_deleted = FALSE
    ");
    $stmt->execute([$user_id, $sub['id']]);
    $new_count = $stmt->fetch()['new_count'];
    
    // Get last message
    $stmt = $db->prepare("
        SELECT m.created_at, u.username
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.sub_id = ? AND m.is_deleted = FALSE
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$sub['id']]);
    $last_message = $stmt->fetch();
    
    $subs_data[] = [
        'sub' => $sub,
        'new_count' => $new_count,
        'last_message' => $last_message
    ];
}

// Start output buffering for content
ob_start();
?>

<h1 class="mb-xl">Welcome back, <?= e(get_current_username()) ?></h1>

<?php foreach ($subs_data as $data): 
    $sub = $data['sub'];
    $new_count = $data['new_count'];
    $last_message = $data['last_message'];
?>
    <div class="sub-card">
        <div class="sub-card-header">
            <h2 class="sub-card-name"><?= e($sub['name']) ?></h2>
            <?php if ($new_count > 0): ?>
                <span class="sub-card-new-count"><?= $new_count ?> new</span>
            <?php endif; ?>
        </div>
        
        <p class="sub-card-description">
            <?= e($sub['description']) ?>
        </p>
        
        <div class="sub-card-meta">
            <div>
                <?php if ($last_message): ?>
                    Last post: <?= time_ago($last_message['created_at']) ?> 
                    by <?= e($last_message['username']) ?>
                <?php else: ?>
                    <span class="text-tertiary">No messages yet</span>
                <?php endif; ?>
            </div>
            
            <div class="sub-card-actions">
                <a href="/?page=sub&id=<?= $sub['id'] ?>" class="btn btn-small">Enter</a>
                <?php if ($new_count > 0): ?>
                    <a href="/?page=mark_read&sub_id=<?= $sub['id'] ?>">Mark all read</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if (empty($subs_data)): ?>
    <div class="status-bar">
        No message boards available yet.
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Dashboard';
$active_page = 'dashboard';
require __DIR__ . '/layout.php';
?>
