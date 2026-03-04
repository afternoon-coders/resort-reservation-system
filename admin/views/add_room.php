<?php
require_once '../auth/auth_functions.php';
require_once '../helpers/DB.php';

requireLogin();
requireAdmin();

$pdo = DB::getPDO();
$types = $pdo->query("SELECT * FROM Cottage_Types")->fetchAll();
$message = '';
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
            $stmt = $pdo->prepare("INSERT INTO Cottages (cottage_number, type_id, base_price, max_occupancy, status, description) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cottageNumber, $typeId, $price, $maxOcc, $status, $description]);
            header('Location: index.php?page=manage_rooms&msg=added');
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<div class="admin-header">
    <h1>Add New Cottage</h1>
    <a href="index.php?page=manage_rooms" class="btn">Back to List</a>
</div>

<div class="card" style="margin-top:20px; max-width: 600px;">
    <?php if ($error): ?>
        <div class="alert alert-danger" style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label class="booknow-label">Cottage Number *</label>
            <input type="text" name="cottage_number" class="booknow-input" required>
        </div>

        <div class="form-group" style="margin-top:15px;">
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
