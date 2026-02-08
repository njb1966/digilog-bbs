<?php
/**
 * Message Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Get sub by ID
 */
function get_sub($sub_id) {
    $db = get_db();
    
    $stmt = $db->prepare("
        SELECT * FROM subs 
        WHERE id = ? AND is_active = TRUE
    ");
    
    $stmt->execute([$sub_id]);
    return $stmt->fetch();
}

/**
 * Get new message count for a sub
 */
function get_new_message_count($user_id, $sub_id) {
    $db = get_db();
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_count
        FROM messages m
        LEFT JOIN read_pointers rp ON rp.sub_id = m.sub_id AND rp.user_id = ?
        WHERE m.sub_id = ? 
        AND m.id > COALESCE(rp.last_read_message_id, 0)
        AND m.is_deleted = FALSE
    ");
    
    $stmt->execute([$user_id, $sub_id]);
    return $stmt->fetch()['new_count'];
}

/**
 * Get messages for a sub (new only)
 */
function get_new_messages($sub_id, $user_id) {
    $db = get_db();
    
    // Get last read pointer
    $stmt = $db->prepare("
        SELECT last_read_message_id, updated_at
        FROM read_pointers 
        WHERE user_id = ? AND sub_id = ?
    ");
    $stmt->execute([$user_id, $sub_id]);
    $pointer = $stmt->fetch();
    
    $last_read_id = $pointer['last_read_message_id'] ?? 0;
    $last_read_time = $pointer['updated_at'] ?? null;
    
    // Get new messages
    $stmt = $db->prepare("
        SELECT m.*, u.username
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.sub_id = ? 
        AND m.id > ?
        AND m.is_deleted = FALSE
        ORDER BY m.id ASC
    ");
    
    $stmt->execute([$sub_id, $last_read_id]);
    $messages = $stmt->fetchAll();
    
    return [
        'messages' => $messages,
        'last_read_id' => $last_read_id,
        'last_read_time' => $last_read_time
    ];
}

/**
 * Get all recent messages for a sub
 */
function get_messages($sub_id, $user_id, $limit = MESSAGES_PER_PAGE) {
    $db = get_db();
    
    // Get last read pointer
    $stmt = $db->prepare("
        SELECT last_read_message_id, updated_at
        FROM read_pointers 
        WHERE user_id = ? AND sub_id = ?
    ");
    $stmt->execute([$user_id, $sub_id]);
    $pointer = $stmt->fetch();
    
    $last_read_id = $pointer['last_read_message_id'] ?? 0;
    $last_read_time = $pointer['updated_at'] ?? null;
    
    // Get messages
    $stmt = $db->prepare("
        SELECT m.*, u.username,
               CASE WHEN m.id > ? THEN 1 ELSE 0 END as is_new
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.sub_id = ? AND m.is_deleted = FALSE
        ORDER BY m.id DESC
        LIMIT ?
    ");
    
    $stmt->execute([$last_read_id, $sub_id, $limit]);
    $messages = array_reverse($stmt->fetchAll()); // Oldest first
    
    return [
        'messages' => $messages,
        'last_read_id' => $last_read_id,
        'last_read_time' => $last_read_time
    ];
}

/**
 * Get a single message by ID
 */
function get_message($message_id) {
    $db = get_db();
    
    $stmt = $db->prepare("
        SELECT m.*, u.username
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.id = ? AND m.is_deleted = FALSE
    ");
    
    $stmt->execute([$message_id]);
    return $stmt->fetch();
}

/**
 * Post a new message
 */
function post_message($sub_id, $user_id, $subject, $body, $parent_id = null) {
    $db = get_db();
    
    // Validate
    if (empty(trim($subject)) || empty(trim($body))) {
        return ['success' => false, 'error' => 'Subject and body are required'];
    }
    
    // If it's a reply, verify parent exists
    if ($parent_id) {
        $parent = get_message($parent_id);
        if (!$parent) {
            return ['success' => false, 'error' => 'Parent message not found'];
        }
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO messages (sub_id, user_id, parent_id, subject, body, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $sub_id,
            $user_id,
            $parent_id,
            trim($subject),
            trim($body),
            get_client_ip()
        ]);
        
        return ['success' => true, 'message_id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        error_log('Post message failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to post message'];
    }
}

/**
 * Update read pointer
 */
function update_read_pointer($user_id, $sub_id) {
    $db = get_db();
    
    // Get highest message ID in sub
    $stmt = $db->prepare("
        SELECT MAX(id) as max_id 
        FROM messages 
        WHERE sub_id = ? AND is_deleted = FALSE
    ");
    $stmt->execute([$sub_id]);
    $max_id = $stmt->fetch()['max_id'];
    
    if (!$max_id) return;
    
    // Upsert read pointer
    $stmt = $db->prepare("
        INSERT INTO read_pointers (user_id, sub_id, last_read_message_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            last_read_message_id = VALUES(last_read_message_id),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$user_id, $sub_id, $max_id]);
}

/**
 * Mark all messages in a sub as read
 */
function mark_sub_read($user_id, $sub_id) {
    update_read_pointer($user_id, $sub_id);
}

/**
 * Get last message in a sub
 */
function get_last_message($sub_id) {
    $db = get_db();
    
    $stmt = $db->prepare("
        SELECT m.created_at, u.username
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.sub_id = ? AND m.is_deleted = FALSE
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$sub_id]);
    return $stmt->fetch();
}
