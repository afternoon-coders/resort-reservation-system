<?php
require_once '../auth/auth_functions.php';
require_once '../helpers/DB.php';

requireLogin();
requireAdmin();

$pdo = DB::getPDO();
$cottageId = $_GET['id'] ?? null;
if (!$cottageId) {
    header('Location: index.php?page=manage_rooms');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM Cottages WHERE cottage_id = ?");
$stmt->execute([$cottageId]);
$cottage = $stmt->fetch();

if (!$cottage) {
    header('Location: index.php?page=manage_rooms');
    exit;
}

$types = $pdo->query("SELECT * FROM Cottage_Types")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cottageNumber = trim($_POST['cottage_number'] ?? '');
    $typeId = $_POST['type_id'] ?? null;
    $price = $_POST['base_price'] ?? 0;
    $maxOcc = $_POST['max_occupancy'] ?? 2;
    $status = $_POST['status'] ?? 'Available';
    $description = trim($_POST['description'] ?? '');

    if (!$cottageNumber || !$typeId) {
        $error = 'Please fill in required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Cottages SET cottage_number = ?, type_id = ?, base_price = ?, max_occupancy = ?, status = ?, description = ? WHERE cottage_id = ?");
            $stmt->execute([$cottageNumber, $typeId, $price, $maxOcc, $status, $description, $cottageId]);
            header('Location: index.php?page=manage_rooms&msg=updated');
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
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
