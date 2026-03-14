<?php
require_once __DIR__ . '/../helpers/admin_backend.php';

$message = '';
$error = '';
$csrfToken = '';

try {
    $pdo = admin_bootstrap();
    $csrfToken = admin_get_csrf_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_require_csrf_token($_POST['csrf_token'] ?? null);

        $result = admin_create_cottage($pdo, $_POST);
        if ($result['ok']) {
            admin_set_flash('success', $result['message']);
            admin_redirect_to_page('manage_rooms');
        }

        $error = $result['message'];
    }

    $types = $pdo->query("SELECT type_id, type_name AS name FROM Cottage_Types ORDER BY type_name ASC")->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
    $types = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    
    <div class="admin-header">
        <h1>Add New Cottage</h1>
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
                <input type="text" name="cottage_number" class="booknow-input" required>
            </div>

            <div class="form-group">
                <label class="booknow-label">Cottage Type *</label>
                <select name="type_id" class="booknow-select" required>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo $t['type_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label class="booknow-label">Base Price (₱)</label>
                <input type="number" name="base_price" step="0.01" class="booknow-input" value="0.00">
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label class="booknow-label">Max Occupancy</label>
                <input type="number" name="max_occupancy" class="booknow-input" value="2">
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label class="booknow-label">Status</label>
                <select name="status" class="booknow-select">
                    <option value="Available">Available</option>
                    <option value="Occupied">Occupied</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Out of Order">Out of Order</option>
                </select>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label class="booknow-label">Description</label>
                <textarea name="description" class="booknow-textarea"></textarea>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Save Cottage</button>
            </div>
        </form>
    </div>

</body>
</html>