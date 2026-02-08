<?php
/**
 * Front Controller / Router
 */

// Load core files
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/messages.php';
require_once __DIR__ . '/../src/email.php';

// Get requested page
$page = $_GET['page'] ?? 'dashboard';

// Routes that don't require login
$public_pages = ['login', 'register', 'privacy'];

// Check if login is required
if (!in_array($page, $public_pages) && !is_logged_in()) {
    $page = 'login';
}

// Track activity for logged-in users
if (is_logged_in()) {
    update_last_activity(get_current_user_id());
}

// Route to appropriate controller
switch ($page) {
    case 'login':
        require __DIR__ . '/../src/views/login.php';
        break;
        
    case 'register':
        require __DIR__ . '/../src/views/register.php';
        break;
        
    case 'logout':
        logout_user();
        redirect('login');
        break;
        
    case 'dashboard':
        require __DIR__ . '/../src/views/dashboard.php';
        break;
        
    case 'sub':
        require __DIR__ . '/../src/views/sub.php';
        break;
        
    case 'sub_all':
        require __DIR__ . '/../src/views/sub_all.php';
        break;
        
    case 'new_thread':
        require __DIR__ . '/../src/views/new_thread.php';
        break;
        
    case 'reply':
        require __DIR__ . '/../src/views/reply.php';
        break;
        
    case 'doors':
        require __DIR__ . '/../src/views/doors.php';
        break;

    case 'door_play':
        require __DIR__ . '/../src/views/door_play.php';
        break;

    case 'door_connect':
        require __DIR__ . '/../src/views/door_connect.php';
        break;
        
    case 'contact':
        require __DIR__ . '/../src/views/contact.php';
        break;
        
    case 'privacy':
        require __DIR__ . '/../src/views/privacy.php';
        break;
        
    case 'user_profile':
        require __DIR__ . '/../src/views/user_profile.php';
        break;

    case 'edit_profile':
        require __DIR__ . '/../src/views/edit_profile.php';
        break;

    case 'change_password':
        require __DIR__ . '/../src/views/change_password.php';
        break;
        
    case 'admin_reset_password':
        require __DIR__ . '/../src/views/admin_reset_password.php';
        break;

    case 'admin':
        require __DIR__ . '/../src/views/admin.php';
        break;
        
    case 'admin_contacts':
        require __DIR__ . '/../src/views/admin_contacts.php';
        break;
        
    case 'admin_users':
        require __DIR__ . '/../src/views/admin_users.php';
        break;

    case 'admin_edit_user':
        require __DIR__ . '/../src/views/admin_edit_user.php';
        break;
        
    case 'admin_messages':
        require __DIR__ . '/../src/views/admin_messages.php';
        break;
        
    case 'mark_read':
        // Quick action to mark sub as read
        $sub_id = (int)($_GET['sub_id'] ?? 0);
        if ($sub_id && is_logged_in()) {
            mark_sub_read(get_current_user_id(), $sub_id);
            set_flash('success', 'Marked all messages as read');
        }
        redirect('dashboard');
        break;
        
    default:
        http_response_code(404);
        echo '404 - Page not found';
        break;
}
