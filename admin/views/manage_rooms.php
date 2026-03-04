<?php
require_once '../auth/auth_functions.php';
require_once '../helpers/DB.php';
require_once '../helpers/RoomModel.php';

requireLogin();
requireAdmin();

$roomModel = new RoomModel();
$rooms = $roomModel->getAll();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $pdo = DB::getPDO();

    if ($action === 'delete_cottage' && !empty($_POST['cottage_id'])) {
        $stmt = $pdo->prepare('DELETE FROM Cottages WHERE cottage_id = :id');
        $stmt->execute([':id' => (int)$_POST['cottage_id']]);
        $message = 'Cottage deleted successfully.';
        $rooms = $roomModel->getAll();
    }

    if ($action === 'update_status' && !empty($_POST['cottage_id']) && isset($_POST['status'])) {
        $stmt = $pdo->prepare('UPDATE Cottages SET status = :s WHERE cottage_id = :id');
        $stmt->execute([':s' => $_POST['status'], ':id' => (int)$_POST['cottage_id']]);
        $message = 'Cottage status updated.';
        $rooms = $roomModel->getAll();
    }
}
?>

<div class="admin-header">
    <h1>Manage Cottages</h1>
    <a href="index.php?page=add_room" class="btn btn-primary">Add New Cottage</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="card" style="margin-top: 20px;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Number</th>
                <th>Type</th>
                <th>Base Price</th>
                <th>Max Occ</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['cottage_id']); ?></td>
                    <td><?php echo htmlspecialchars($r['cottage_number']); ?></td>
                    <td><?php echo htmlspecialchars($r['name'] ?? '—'); ?></td>
                    <td>₱<?php echo number_format($r['base_price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($r['max_occupancy']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="cottage_id" value="<?php echo $r['cottage_id']; ?>">
                            <select name="status" class="badge" onchange="this.form.submit()">
                                <option value="Available" <?php echo $r['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Occupied" <?php echo $r['status'] === 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                                <option value="Maintenance" <?php echo $r['status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <div class="action-btn-container">
                            <a href="index.php?page=edit_room&id=<?php echo $r['cottage_id']; ?>" class="refresh-btn">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this cottage?');">
                                <input type="hidden" name="action" value="delete_cottage">
                                <input type="hidden" name="cottage_id" value="<?php echo $r['cottage_id']; ?>">
                                <button type="submit" class="delete-btn">
                                    <img src="/admin/static/img//adminpanel_icons/delete.svg" alt="">
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
