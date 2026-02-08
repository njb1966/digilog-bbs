<?php
/**
 * Helper Functions
 */

/**
 * Sanitize output for HTML display
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Convert timestamp to relative time (e.g., "2 hours ago")
 */
function time_ago($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Redirect to a page
 */
function redirect($page, $params = []) {
    $url = '/?page=' . urlencode($page);
    
    foreach ($params as $key => $value) {
        $url .= '&' . urlencode($key) . '=' . urlencode($value);
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * Get current user ID from session
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username from session
 */
function get_current_username() {
    return $_SESSION['username'] ?? null;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 */
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Require login (redirect to login if not logged in)
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login');
    }
}

/**
 * Set flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get client IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Format message body: escape HTML and style quoted lines
 */
function format_message_body($body) {
    $escaped = e($body);
    $lines = explode("\n", $escaped);
    $result = [];
    foreach ($lines as $line) {
        if (preg_match('/^(&gt;\s?)/', $line)) {
            $result[] = '<span class="quoted-line">' . $line . '</span>';
        } else {
            $result[] = $line;
        }
    }
    return implode("\n", $result);
}

/**
 * Update last activity timestamp (throttled to once per minute)
 */
function update_last_activity($user_id) {
    if (isset($_SESSION['last_activity_update']) && (time() - $_SESSION['last_activity_update']) < 60) {
        return;
    }
    $db = get_db();
    $stmt = $db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['last_activity_update'] = time();
}

/**
 * Get user profile by ID (public fields + post count)
 */
function get_user_profile($user_id) {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.bio, u.location, u.created_at, u.last_login,
               COUNT(m.id) as post_count
        FROM users u
        LEFT JOIN messages m ON u.id = m.user_id AND m.is_deleted = FALSE
        WHERE u.id = ? AND u.is_active = TRUE
        GROUP BY u.id
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Update user profile bio and location
 */
function update_user_profile($user_id, $bio, $location) {
    $db = get_db();
    $stmt = $db->prepare("UPDATE users SET bio = ?, location = ? WHERE id = ?");
    $stmt->execute([$bio, $location, $user_id]);
}

/**
 * Get currently online users (active in last 5 minutes)
 */
function get_online_users() {
    $db = get_db();
    $stmt = $db->query("
        SELECT id, username
        FROM users
        WHERE last_activity > NOW() - INTERVAL 5 MINUTE
        AND is_active = TRUE
        ORDER BY username ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Get last callers (most recent logins, excluding online users)
 */
function get_last_callers($limit = 5) {
    $db = get_db();
    $stmt = $db->prepare("
        SELECT id, username, last_login
        FROM users
        WHERE is_active = TRUE
        AND last_login IS NOT NULL
        AND (last_activity IS NULL OR last_activity <= NOW() - INTERVAL 5 MINUTE)
        ORDER BY last_login DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Validate email address
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username (alphanumeric, underscore, hyphen)
 */
function is_valid_username($username) {
    return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username);
}

/**
 * Admin: update user profile fields
 */
function admin_update_user($user_id, $username, $email, $bio, $location, $email_verified) {
    if (!is_valid_username($username)) {
        return ['success' => false, 'error' => 'Invalid username format (3-50 chars, letters, numbers, underscore, hyphen)'];
    }

    if (!is_valid_email($email)) {
        return ['success' => false, 'error' => 'Invalid email address'];
    }

    if (strlen($bio) > 500) {
        return ['success' => false, 'error' => 'Bio must be 500 characters or less'];
    }

    if (strlen($location) > 100) {
        return ['success' => false, 'error' => 'Location must be 100 characters or less'];
    }

    $db = get_db();

    // Check username uniqueness (excluding this user)
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username already taken by another user'];
    }

    // Check email uniqueness (excluding this user)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already used by another user'];
    }

    $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, bio = ?, location = ?, email_verified = ? WHERE id = ?");
    $stmt->execute([$username, $email, $bio, $location, $email_verified ? 1 : 0, $user_id]);

    return ['success' => true];
}

/**
 * Check honeypot field - returns true if bot detected (field was filled)
 */
function check_honeypot() {
    return !empty($_POST['website']);
}

/**
 * Verify Cloudflare Turnstile token
 * Returns true if valid or if Turnstile is not configured
 */
function verify_turnstile($token) {
    if (empty(TURNSTILE_SECRET_KEY)) {
        return true;
    }

    if (empty($token)) {
        return false;
    }

    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => get_client_ip()
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);

    if ($response === false) {
        error_log('Turnstile verification request failed');
        return false;
    }

    $result = json_decode($response, true);
    return isset($result['success']) && $result['success'] === true;
}

/**
 * Check if IP is within registration rate limit
 * Returns true if allowed, false if rate limited
 */
function check_registration_rate_limit($ip) {
    $db = get_db();

    // Clean up old entries (older than 24 hours)
    $db->exec("DELETE FROM registration_attempts WHERE attempted_at < NOW() - INTERVAL 24 HOUR");

    // Count recent attempts
    $stmt = $db->prepare("SELECT COUNT(*) FROM registration_attempts WHERE ip_address = ? AND attempted_at > NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$ip]);
    $count = (int)$stmt->fetchColumn();

    return $count < REGISTRATION_RATE_LIMIT;
}

/**
 * Log a registration attempt for rate limiting
 */
function log_registration_attempt($ip) {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)");
    $stmt->execute([$ip]);
}
