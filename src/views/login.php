<?php
/**
 * Login Page
 */

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        set_flash('error', 'Please enter both username and password');
    } else {
        $result = login_user($username, $password);
        
        if ($result['success']) {
            // Check if there's a redirect URL
            $redirect_to = $_SESSION['redirect_after_login'] ?? 'dashboard';
            unset($_SESSION['redirect_after_login']);
            
            set_flash('success', 'Welcome back, ' . e($result['user']['username']) . '!');
            redirect($redirect_to);
        } else {
            set_flash('error', $result['error']);
        }
    }
}

// Start output buffering for content
ob_start();
?>

<div style="max-width: 500px; margin: 4rem auto;">
    <h1 class="mb-lg">Login</h1>
    
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-xl);">
        <form method="POST" action="/?page=login">
            <div class="form-group">
                <label class="form-label" for="username">Username or Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username"
                    class="form-input" 
                    placeholder="Enter your username or email"
                    required
                    autofocus
                    value="<?= e($_POST['username'] ?? '') ?>"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    class="form-input" 
                    placeholder="Enter your password"
                    required
                >
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
        
        <div style="margin-top: var(--space-lg); padding-top: var(--space-lg); border-top: 1px solid var(--border); text-align: center; color: var(--text-secondary); font-size: 0.95rem;">
            Don't have an account? <a href="/?page=register">Register here</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Login';
$active_page = 'login';
require __DIR__ . '/layout.php';
?>
