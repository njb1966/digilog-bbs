<?php
/**
 * Registration Page
 */

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_ip = get_client_ip();

    // Honeypot check — silently fake success to not tip off bots
    if (check_honeypot()) {
        set_flash('success', 'Account created successfully! Please log in.');
        redirect('login');
    }

    // Log attempt and check rate limit
    log_registration_attempt($client_ip);
    if (!check_registration_rate_limit($client_ip)) {
        set_flash('error', 'Too many registration attempts. Please try again later.');
    } else {
        // Turnstile verification
        $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
        if (!verify_turnstile($turnstile_token)) {
            set_flash('error', 'Please complete the verification challenge.');
        } else {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            $privacy_agree = isset($_POST['privacy_agree']);

            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                set_flash('error', 'All fields are required');
            } elseif (!$privacy_agree) {
                set_flash('error', 'You must agree to the Privacy Policy');
            } elseif ($password !== $password_confirm) {
                set_flash('error', 'Passwords do not match');
            } else {
                $result = register_user($username, $email, $password);

                if ($result['success']) {
                    set_flash('success', 'Account created successfully! Please log in.');
                    redirect('login');
                } else {
                    set_flash('error', $result['error']);
                }
            }
        }
    }
}

// Start output buffering for content
ob_start();
?>

<div style="max-width: 500px; margin: 4rem auto;">
    <h1 class="mb-lg">Create Account</h1>
    
    <div style="background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: var(--space-xl);">
        <form method="POST" action="/?page=register">
            <!-- Honeypot field — hidden from humans, bots will fill it -->
            <div class="hp-field" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username"
                    class="form-input" 
                    placeholder="Choose a username"
                    required
                    autofocus
                    pattern="[a-zA-Z0-9_-]{3,50}"
                    title="3-50 characters, letters, numbers, underscore, and hyphen only"
                    value="<?= e($_POST['username'] ?? '') ?>"
                >
                <small class="text-tertiary">3-50 characters, letters, numbers, underscore, and hyphen only</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email"
                    class="form-input" 
                    placeholder="your@email.com"
                    required
                    value="<?= e($_POST['email'] ?? '') ?>"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    class="form-input" 
                    placeholder="Choose a strong password"
                    required
                    minlength="<?= PASSWORD_MIN_LENGTH ?>"
                >
                <small class="text-tertiary">Minimum <?= PASSWORD_MIN_LENGTH ?> characters</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password_confirm">Confirm Password</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm"
                    class="form-input" 
                    placeholder="Re-enter your password"
                    required
                >
            </div>
            
            <div style="padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-sm); margin-bottom: var(--space-lg);">
                <label style="display: flex; align-items: start; cursor: pointer;">
                    <input type="checkbox" name="privacy_agree" required style="margin-right: 0.5rem; margin-top: 0.25rem;">
                    <span style="font-size: 0.875rem; color: var(--text-secondary);">
                        I agree to the <a href="/?page=privacy" target="_blank">Privacy Policy</a> 
                        and understand how my data will be used.
                    </span>
                </label>
            </div>
            
            <?php if (!empty(TURNSTILE_SITE_KEY)): ?>
            <div class="form-group">
                <div class="cf-turnstile" data-sitekey="<?= e(TURNSTILE_SITE_KEY) ?>" data-theme="dark"></div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Account</button>
                <a href="/?page=login">Cancel</a>
            </div>
        </form>
        
        <div style="margin-top: var(--space-lg); padding-top: var(--space-lg); border-top: 1px solid var(--border); text-align: center; color: var(--text-secondary); font-size: 0.95rem;">
            Already have an account? <a href="/?page=login">Login here</a>
        </div>
    </div>
</div>

<?php if (!empty(TURNSTILE_SITE_KEY)): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Register';
$active_page = 'register';
require __DIR__ . '/layout.php';
?>
