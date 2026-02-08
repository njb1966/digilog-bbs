<?php
/**
 * Configuration and Database Connection
 */

// Load environment variables
function load_env($file = '../.env') {
    if (!file_exists($file)) {
        die('Error: .env file not found');
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Skip lines without =
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes if present
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        
        // Set in $_ENV
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// Load environment variables
load_env(__DIR__ . '/../.env');

// Database connection
function get_db() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=utf8mb4",
                $_ENV['DB_HOST'],
                $_ENV['DB_NAME']
            );
            
            $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Please check configuration.');
        }
    }
    
    return $pdo;
}

// Site constants
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Digilog');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@localhost');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://bbs.local');
define('MESSAGES_PER_PAGE', 50);
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 86400));
define('PASSWORD_MIN_LENGTH', (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 8));
define('TURNSTILE_SITE_KEY', $_ENV['TURNSTILE_SITE_KEY'] ?? '');
define('TURNSTILE_SECRET_KEY', $_ENV['TURNSTILE_SECRET_KEY'] ?? '');
define('REGISTRATION_RATE_LIMIT', (int)($_ENV['REGISTRATION_RATE_LIMIT'] ?? 3));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => SESSION_LIFETIME,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}
