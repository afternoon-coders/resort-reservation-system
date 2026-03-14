<?php
require_once __DIR__ . '/../helpers/admin_backend.php';

$error = '';
$csrfToken = '';
$types = [];
$cottage = null;

try {
    $pdo = admin_bootstrap();
    $csrfToken = admin_get_csrf_token();

    $cottageId = admin_positive_int($_GET['id'] ?? null);
    if ($cottageId === null) {
        admin_set_flash('error', 'Invalid cottage id.');
        admin_redirect_to_page('manage_rooms');
    }

    $stmt = $pdo->prepare('SELECT * FROM Cottages WHERE cottage_id = :id LIMIT 1');
    $stmt->execute([':id' => $cottageId]);
    $cottage = $stmt->fetch();

    if (!$cottage) {
        admin_set_flash('error', 'Cottage not found.');
        admin_redirect_to_page('manage_rooms');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_require_csrf_token($_POST['csrf_token'] ?? null);

        $result = admin_update_cottage($pdo, $cottageId, $_POST);
        if ($result['ok']) {
            admin_set_flash('success', $result['message']);
            admin_redirect_to_page('manage_rooms');
        }

        $error = $result['message'];
        $cottage['cottage_number'] = trim((string)($_POST['cottage_number'] ?? $cottage['cottage_number']));
        $cottage['type_id'] = $_POST['type_id'] ?? $cottage['type_id'];
        $cottage['base_price'] = $_POST['base_price'] ?? $cottage['base_price'];
        $cottage['max_occupancy'] = $_POST['max_occupancy'] ?? $cottage['max_occupancy'];
        $cottage['status'] = $_POST['status'] ?? $cottage['status'];
        $cottage['description'] = trim((string)($_POST['description'] ?? $cottage['description']));
    }

    $types = $pdo->query("SELECT type_id, type_name AS name FROM Cottage_Types ORDER BY type_name ASC")->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

if (!$cottage) {
    $cottage = [
        'cottage_number' => '',
        'type_id' => '',
        'base_price' => '0.00',
        'max_occupancy' => '2',
        'status' => 'Available',
        'description' => '',
    ];
}
?>

<div class="admin-header">
    <h1>Edit Cottage #<?php echo htmlspecialchars($cottage['cottage_number']); ?></h1>
    <a href="index.php?page=manage_rooms" class="btn">Back to List</a>
</div>

<div class="card" style="margin-top:20px; max-width: 600px;">
    <?php if ($error): ?>
        <div class="alert alert-danger" style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <div class="form-group">
            <label class="booknow-label">Cottage Number *</label>
            <input type="text" name="cottage_number" class="booknow-input" value="<?php echo htmlspecialchars($cottage['cottage_number']); ?>" required>
        </div>

        <div class="form-group" style="margin-top:15px;">
            <label class="booknow-label">Cottage Type *</label>
            <select name="type_id" class="booknow-select" required>
                <?php foreach ($types as $t): ?>
                    <option value="<?php echo $t['type_id']; ?>" <?php echo $cottage['type_id'] == $t['type_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-top:15px;">
            <label class="booknow-label">Base Price (₱)</label>
            <input type="number" name="base_price" step="0.01" class="booknow-input" value="<?php echo htmlspecialchars($cottage['base_price']); ?>">
        </div>

        <div class="form-group" style="margin-top:15px;">
            <label class="booknow-label">Max Occupancy</label>
            <input type="number" name="max_occupancy" class="booknow-input" value="<?php echo htmlspecialchars($cottage['max_occupancy']); ?>">
        </div>

        <div class="form-group" style="margin-top:15px;">
            <label class="booknow-label">Status</label>
            <select name="status" class="booknow-select">
                <option value="Available" <?php echo $cottage['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                <option value="Occupied" <?php echo $cottage['status'] === 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                <option value="Maintenance" <?php echo $cottage['status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                <option value="Out of Order" <?php echo $cottage['status'] === 'Out of Order' ? 'selected' : ''; ?>>Out of Order</option>
            </select>
        </div>

        <div class="form-group" style="margin-top:15px;">
            <label class="booknow-label">Description</label>
            <textarea name="description" class="booknow-textarea"><?php echo htmlspecialchars($cottage['description']); ?></textarea>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">Update Cottage</button>
        </div>
    </form>
</div>
