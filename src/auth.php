<?php
/**
 * Authentication Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Attempt to log in a user
 */
function login_user($username, $password) {
    $db = get_db();
    
    $stmt = $db->prepare("
        SELECT id, username, email, password_hash, is_admin, is_active 
        FROM users 
        WHERE username = ? OR email = ?
    ");
    
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    if (!$user['is_active']) {
        return ['success' => false, 'error' => 'Account is disabled'];
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Register a new user
 */
function register_user($username, $email, $password) {
    $db = get_db();
    
    // Validate input
    if (!is_valid_username($username)) {
        return ['success' => false, 'error' => 'Invalid username format'];
    }
    
    if (!is_valid_email($email)) {
        return ['success' => false, 'error' => 'Invalid email address'];
    }
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return ['success' => false, 'error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
    }
    
    // Check if username exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username already exists'];
    }
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    try {
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, is_active, email_verified)
            VALUES (?, ?, ?, TRUE, FALSE)
        ");
        
        $stmt->execute([$username, $email, $password_hash]);
        
        return ['success' => true, 'user_id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        error_log('Registration failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

/**
 * Log out current user
 */
function logout_user() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Get user by ID
 */
function get_user_by_id($user_id) {
    $db = get_db();
    
    $stmt = $db->prepare("
        SELECT id, username, email, created_at, last_login, is_admin 
        FROM users 
        WHERE id = ? AND is_active = TRUE
    ");
    
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
