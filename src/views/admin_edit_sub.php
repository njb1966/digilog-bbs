<?php
/**
 * Admin - Create/Edit Sub (Message Board)
 */

// Require admin access
if (!is_admin()) {
    set_flash('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

$db = get_db();
$sub_id = (int)($_GET['id'] ?? 0);
$is_edit = $sub_id > 0;
$sub = null;

// Load existing sub for edit mode
if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM subs WHERE id = ?");
    $stmt->execute([$sub_id]);
    $sub = $stmt->fetch();

    if (!$sub) {
        set_flash('error', 'Sub not found');
        redirect('admin_subs');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $position = (int)($_POST['position'] ?? 0);

    if (!verify_csrf_token($csrf_token)) {
        set_flash('error', 'Invalid security token. Please try again.');
    } elseif (empty($name)) {
        set_flash('error', 'Name is required');
    } elseif (empty($slug)) {
        set_flash('error', 'Slug is required');
    } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        set_flash('error', 'Slug must be lowercase letters, numbers, and hyphens only (e.g. "my-board")');
    } elseif (mb_strlen($name) > 100) {
        set_flash('error', 'Name must be 100 characters or less');
    } elseif (mb_strlen($slug) > 100) {
        set_flash('error', 'Slug must be 100 characters or less');
    } else {
        // Check slug uniqueness
        $slug_query = "SELECT id FROM subs WHERE slug = ?";
        $slug_params = [$slug];
        if ($is_edit) {
            $slug_query .= " AND id != ?";
            $slug_params[] = $sub_id;
        }
        $stmt = $db->prepare($slug_query);
        $stmt->execute($slug_params);

        if ($stmt->fetch()) {
            set_flash('error', 'A sub with that slug already exists');
        } else {
            if ($is_edit) {
                $stmt = $db->prepare("UPDATE subs SET name = ?, slug = ?, description = ?, position = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $position, $sub_id]);
                set_flash('success', 'Sub "' . e($name) . '" updated');
            } else {
                $stmt = $db->prepare("INSERT INTO subs (name, slug, description, position) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $position]);
                set_flash('success', 'Sub "' . e($name) . '" created');
            }
            redirect('admin_subs');
        }
    }
}

// Start output buffering for content
ob_start();
?>

<div class="breadcrumb">
    <div class="breadcrumb-path">
        <a href="/?page=admin">Admin</a>
        <span>&gt;</span>
        <a href="/?page=admin_subs">Manage Subs</a>
        <span>&gt;</span>
        <span><?= $is_edit ? 'Edit Sub' : 'Create Sub' ?></span>
    </div>
</div>

<h1 class="mb-lg"><?= $is_edit ? 'Edit Sub: ' . e($sub['name']) : 'Create New Sub' ?></h1>

<form method="POST" action="/?page=admin_edit_sub<?= $is_edit ? '&id=' . $sub_id : '' ?>" style="max-width: 600px;">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

    <div class="form-group">
        <label class="form-label" for="name">Name</label>
        <input
            type="text"
            id="name"
            name="name"
            class="form-input"
            required
            maxlength="100"
            value="<?= e($_POST['name'] ?? $sub['name'] ?? '') ?>"
        >
    </div>

    <div class="form-group">
        <label class="form-label" for="slug">Slug</label>
        <input
            type="text"
            id="slug"
            name="slug"
            class="form-input"
            required
            maxlength="100"
            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
            title="Lowercase letters, numbers, and hyphens only"
            value="<?= e($_POST['slug'] ?? $sub['slug'] ?? '') ?>"
        >
        <small class="text-tertiary">Lowercase letters, numbers, and hyphens only (e.g. "my-board")</small>
    </div>

    <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea
            id="description"
            name="description"
            class="form-textarea"
            style="min-height: 120px;"
        ><?= e($_POST['description'] ?? $sub['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label class="form-label" for="position">Position</label>
        <input
            type="number"
            id="position"
            name="position"
            class="form-input"
            min="0"
            value="<?= e($_POST['position'] ?? $sub['position'] ?? '0') ?>"
        >
        <small class="text-tertiary">Controls sort order on the dashboard (lower numbers appear first)</small>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Save Changes' : 'Create Sub' ?></button>
        <a href="/?page=admin_subs" class="btn">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
$page_title = ($is_edit ? 'Edit Sub' : 'Create Sub') . ' - Admin';
$active_page = 'admin';
require __DIR__ . '/layout.php';
?>
