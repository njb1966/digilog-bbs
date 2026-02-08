<?php
/**
 * Door Games - BBSLink Game Listing
 */

require_once __DIR__ . '/../door_manager.php';

$door_manager = new DoorManager();
$games = $door_manager->getAvailableGames();

ob_start();
?>

<h1 class="mb-lg">Door Games</h1>

<p class="text-secondary" style="margin-bottom: var(--space-xl); max-width: 700px; line-height: 1.6;">
    Classic BBS door games powered by BBSLink. Click Play to launch a game right in your browser.
    Games run in an 80x24 terminal with authentic DOS ANSI graphics.
</p>

<div class="door-grid">
<?php foreach ($games as $game): ?>
    <div class="door-card">
        <div class="door-card-header">
            <h3><?= e($game['name']) ?></h3>
            <span class="door-card-category"><?= e($game['category']) ?></span>
        </div>
        <p class="door-card-description"><?= e($game['description']) ?></p>
        <a href="/?page=door_play&door=<?= e($game['code']) ?>" class="btn btn-primary btn-small">Play</a>
    </div>
<?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Door Games';
$active_page = 'doors';
require __DIR__ . '/layout.php';
?>
