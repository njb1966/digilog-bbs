<?php
/**
 * Master Layout Template
 * Variables expected:
 * - $page_title: Page title
 * - $content: Main content HTML
 * - $active_page: Current page for nav highlighting
 */

$flash = get_flash();
$_sidebar_online = is_logged_in() ? get_online_users() : [];
$_sidebar_callers = is_logged_in() ? get_last_callers(5) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=20260208b">
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="/assets/images/logo.svg" alt="<?= e(SITE_NAME) ?>" style="max-width: 100%; height: auto;">
            </div>
            
            <nav>
                <ul class="sidebar-nav">
                    <li>
                        <a href="/?page=dashboard" class="<?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="/?page=doors" class="<?= ($active_page ?? '') === 'doors' ? 'active' : '' ?>">
                            Doors
                        </a>
                    </li>
                    <li>
                        <a href="/?page=contact" class="<?= ($active_page ?? '') === 'contact' ? 'active' : '' ?>">
                            Contact
                        </a>
                    </li>
                    <li>
                        <a href="/?page=privacy" class="<?= ($active_page ?? '') === 'privacy' ? 'active' : '' ?>">
                            Privacy Policy
                        </a>
                    </li>
                </ul>
            </nav>

            <?php if (is_logged_in()): ?>
            <div class="sidebar-widget">
                <div class="sidebar-widget-title">Online</div>
                <ul class="sidebar-widget-list">
                    <?php if (empty($_sidebar_online)): ?>
                        <li class="text-tertiary">Nobody right now</li>
                    <?php else: ?>
                        <?php foreach ($_sidebar_online as $u): ?>
                            <li><a href="/?page=user_profile&id=<?= $u['id'] ?>"><?= e($u['username']) ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <?php if (!empty($_sidebar_callers)): ?>
                    <div class="sidebar-widget-title" style="margin-top: var(--space-md);">Last Callers</div>
                    <ul class="sidebar-widget-list">
                        <?php foreach ($_sidebar_callers as $u): ?>
                            <li><a href="/?page=user_profile&id=<?= $u['id'] ?>"><?= e($u['username']) ?></a> <span class="text-tertiary"><?= time_ago($u['last_login']) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="sidebar-footer">
                &copy;<?= date('Y') ?> <?= e(SITE_NAME) ?>
            </div>
        </aside>
        
        <!-- Main content -->
        <div class="main-wrapper">
            <header>
                <div class="container">
                    <div class="site-name"><?= SITE_NAME ?></div>
                    <?php if (is_logged_in()): ?>
                    <div class="user-menu">
                        <span class="username"><?= e(get_current_username()) ?></span>
                        <?php if (is_admin()): ?>
                            <a href="/?page=admin">Admin</a>
                        <?php endif; ?>
                        <a href="/?page=edit_profile">My Profile</a>
                        <a href="/?page=change_password">Change Password</a>
                        <a href="/?page=logout">Logout</a>
                    </div>
                    <?php endif; ?>
                </div>
            </header>
            
            <main>
                <div class="container">
                    <?php if ($flash): ?>
                        <div class="flash-message flash-<?= e($flash['type']) ?>">
                            <?= e($flash['message']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?= $content ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
