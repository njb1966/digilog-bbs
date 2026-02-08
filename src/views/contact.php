<?php
/**
 * Contact Page
 */

require_once __DIR__ . '/../email.php';

// Pre-fill if user is logged in
$default_name = '';
$default_email = '';

if (is_logged_in()) {
    $user = get_user_by_id(get_current_user_id());
    if ($user) {
        $default_name = $user['username'];
        $default_email = $user['email'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validation
    if (!verify_csrf_token($csrf_token)) {
        set_flash('error', 'Invalid security token. Please try again.');
    } elseif (empty($name) || empty($email) || empty($subject) || empty($message)) {
        set_flash('error', 'All fields are required');
    } elseif (!is_valid_email($email)) {
        set_flash('error', 'Invalid email address');
    } else {
        // Save to database
        $save_result = save_contact_message(
            $name, 
            $email, 
            $subject, 
            $message, 
            is_logged_in() ? get_current_user_id() : null
        );
        
        if ($save_result['success']) {
            // Send email notification
            $email_result = send_contact_notification($name, $email, $subject, $message);
            
            if ($email_result['success']) {
                set_flash('success', 'Message sent successfully! We\'ll get back to you soon.');
                // Clear form
                $_POST = [];
            } else {
                set_flash('error', 'Message saved but email notification failed. We\'ll review it soon.');
            }
        } else {
            set_flash('error', 'Failed to send message. Please try again.');
        }
    }
}

// Start output buffering for content
ob_start();
?>

<h1 class="mb-lg">Contact</h1>

<p class="text-secondary mb-xl">
    Have a question, found an issue, or just want to say hello? Send us a message.
</p>

<form method="POST" action="/?page=contact" style="max-width: 600px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    
    <div class="form-group">
        <label class="form-label" for="name">Your Name</label>
        <input 
            type="text" 
            id="name" 
            name="name"
            class="form-input" 
            placeholder="Enter your name"
            required
            value="<?= e($_POST['name'] ?? $default_name) ?>"
        >
    </div>
    
    <div class="form-group">
        <label class="form-label" for="email">Your Email</label>
        <input 
            type="email" 
            id="email" 
            name="email"
            class="form-input" 
            placeholder="your@email.com"
            required
            value="<?= e($_POST['email'] ?? $default_email) ?>"
        >
    </div>
    
    <div class="form-group">
        <label class="form-label" for="subject">Subject</label>
        <input 
            type="text" 
            id="subject" 
            name="subject"
            class="form-input" 
            placeholder="Brief subject line"
            required
            maxlength="255"
            value="<?= e($_POST['subject'] ?? '') ?>"
        >
    </div>
    
    <div class="form-group">
        <label class="form-label" for="message">Message</label>
        <textarea 
            id="message" 
            name="message"
            class="form-textarea" 
            placeholder="Your message here..."
            required
        ><?= e($_POST['message'] ?? '') ?></textarea>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Send Message</button>
        <button type="reset" class="btn">Clear Form</button>
    </div>
</form>

<div style="margin-top: 2rem; padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 0.95rem; max-width: 600px;">
    <strong>Note:</strong> Messages are sent via ProtonMail and saved to our database. 
    You should receive a confirmation once your message is sent. We typically respond within 24-48 hours.
</div>

<?php
$content = ob_get_clean();
$page_title = 'Contact';
$active_page = 'contact';
require __DIR__ . '/layout.php';
?>
