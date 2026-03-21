<?php
require_once '../auth/auth_functions.php';
require_once '../helpers/DB.php';
require_once '../helpers/RoomModel.php';
require_once '../inc/csrf.php';

    require_once __DIR__ . '/../helpers/admin_backend.php';
    require_once __DIR__ . '/../../helpers/RoomModel.php';

    $message = '';
    $error = '';
    $csrfToken = '';

    try {
        $pdo = admin_bootstrap();
        $csrfToken = admin_get_csrf_token();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            admin_require_csrf_token($_POST['csrf_token'] ?? null);

            $action = trim((string)($_POST['action'] ?? ''));
            if ($action === '') {
                $action = 'create_cottage';
            }

            $result = admin_dispatch_action($pdo, $action, $_POST);
            admin_set_flash($result['ok'] ? 'success' : 'error', $result['message']);

            admin_redirect_to_page('manage_rooms');
        }

        $flash = admin_pop_flash();
        if ($flash !== null) {
            if (($flash['type'] ?? '') === 'error') {
                $error = $flash['message'];
            } else {
                $message = $flash['message'];
            }
        }

        $roomModel = new RoomModel();
        $rooms = $roomModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify_or_die();
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

// Calculate Stats
$roomsTotal = $pdo->query("SELECT COUNT(*) FROM Cottages")->fetchColumn();
$roomsAvailable = $pdo->query("SELECT COUNT(*) FROM Cottages WHERE status = 'Available'")->fetchColumn();
$reservationsTotal = $pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
$cottageTypes = $pdo->query("SELECT COUNT(*) FROM Cottage_Types")->fetchColumn();

$types = $pdo->query("SELECT * FROM Cottage_Types")->fetchAll();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();

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
            error_log('Manage rooms error: ' . $e->getMessage());
            $error = 'An error occurred while adding the cottage. Please try again.';
        }
    }
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

    <!-- Floating JS Alert -->
    <div id="js-alert"></div>

    <?php if ($message): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showAlert('success', '<?php echo addslashes(htmlspecialchars($message)); ?>'));</script>
    <?php endif; ?>
    <?php if ($error): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showAlert('error', '<?php echo addslashes(htmlspecialchars($error)); ?>'));</script>
    <?php endif; ?>

    <div class="admin-header">
        <span style="display:flex; justify-content: space-between; align-items: center;">
            <h1>Manage Cottages</h1>
            <button type="button" onclick="showForm()" class="btn" style="width: 170px; justify-content: flex-start;">
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff"><path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/></svg>
                </span>
                Add Cottage
            </button>
        </span>
        <p>Manage room inventory, pricing, and content</p>
    </div>

    <div class="manage-room-parent">
        <div class="div1 div-cards">
            <div class="card-stat">
                <h2><?php echo ($roomsTotal); ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Cottages</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
        </div>

        <div class="div2">
            <div class="card-stat">
                <h2><?php echo ($roomsAvailable); ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Available</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
        </div>

        <div class="div3 div-cards">
            <div class="card-stat">
                <h2><?php echo ($cottageTypes); ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Cottage Types</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
        </div>

        <div class="div4">
            <div class="card">
                <table class="cottages-table">
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
                                        <?php echo csrf_field(); ?>
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
                                        <a href="index.php?page=edit_room&id=<?php echo $r['cottage_id']; ?>" class="edit-btn">
                                            <img src="static/img/adminpanel_icons/edit.svg" alt="">
                                        </a>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this cottage?');">
                                            <?php echo csrf_field(); ?>
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
        </div>

        <div class="div5">
            <div class="modal-wrapper">
                <div class="card">
                    <h3 style="margin-bottom: 0px;">Edit Room</h1>
                    <p style="font-size: 11px; margin-bottom: 20px;">Update room information</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label class="edit-cottage-label">Cottage Number *</label>
                            <input type="text" name="cottage_number" class="booknow-input" required>
                        </div>

                            <div class="form-group">
                                <label class="edit-cottage-label">Cottage Type *</label>
                                <select name="type_id" id="form-type-id" class="booknow-select"
                                        style="background-color: transparent; border: 1.5px solid #e2e8f0;" required>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?php echo $t['type_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-top:15px;">
                                <label class="edit-cottage-label">Base Price (₱)</label>
                                <input type="number" name="base_price" id="form-base-price" step="0.01"
                                    class="booknow-input" style="background-color: transparent; border: 1.5px solid #e2e8f0;" value="0.00">
                            </div>

                            <div class="form-group" style="margin-top:15px;">
                                <label class="edit-cottage-label">Max Occupancy</label>
                                <input type="number" name="max_occupancy" id="form-max-occupancy"
                                    class="booknow-input" style="background-color: transparent; border: 1.5px solid #e2e8f0;" value="2">
                            </div>

                            <div class="form-group" style="margin-top:15px;">
                                <label class="edit-cottage-label">Status</label>
                                <select name="status" id="form-status" class="booknow-select">
                                    <option value="Available">Available</option>
                                    <option value="Occupied">Occupied</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Out of Order">Out of Order</option>
                                </select>
                            </div>

                            <div style="margin-top:20px;">
                                <button type="submit" class="btn btn-primary">
                                    <span>
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff">
                                            <path d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM565-275q35-35 35-85t-35-85q-35-35-85-35t-85 35q-35 35-35 85t35 85q35 35 85 35t85-35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"/>
                                        </svg>
                                    </span>
                                    <span id="form-btn-text">Save Cottage</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function showAlert(type, message) {
            const alert = document.getElementById('js-alert');

            if (type === 'success') {
                alert.style.backgroundColor = '#d4edda';
                alert.style.color = '#155724';
                alert.style.border = '1px solid #c3e6cb';
            } else {
                alert.style.backgroundColor = '#f8d7da';
                alert.style.color = '#721c24';
                alert.style.border = '1px solid #f5c6cb';
            }

            alert.textContent = message;
            alert.style.display = 'block';
            alert.style.opacity = '1';

            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 400);
            }, 3000);
        }

        function updateSelectClass(select) {
            select.className = 'badge';
            if (select.value === 'Available') {
                select.classList.add('badge-available');
            } else if (select.value === 'Occupied') {
                select.classList.add('badge-occupied');
            } else if (select.value === 'Maintenance') {
                select.classList.add('badge-maintenance');
            } else if (select.value === 'Out of Order') {
                select.classList.add('badge-out-of-order');
            }
        }

        document.querySelectorAll('select.badge').forEach(updateSelectClass);
        
        function showForm() {
            // Reset form to Add mode
            document.getElementById('cottage-form').reset();
            document.getElementById('form-action').value         = 'create_cottage';
            document.getElementById('form-cottage-id').value    = '';
            document.getElementById('form-title').textContent    = 'Add Cottage';
            document.getElementById('form-subtitle').textContent = 'Fill in cottage information';
            document.getElementById('form-btn-text').textContent = 'Save Cottage';

            // Show form, hide empty state
            document.getElementById('empty-state').style.display        = 'none';
            document.getElementById('cottage-form-wrapper').style.display = 'block';

            document.querySelector('.div5').scrollIntoView({ behavior: 'smooth' });
        }

        function openEditForm(id, number, typeId, price, occupancy, status) {
            showForm();
            document.getElementById('form-action').value          = 'update_cottage';
            document.getElementById('form-cottage-id').value      = id;
            document.getElementById('form-cottage-number').value  = number;
            document.getElementById('form-type-id').value         = typeId;
            document.getElementById('form-base-price').value      = price;
            document.getElementById('form-max-occupancy').value   = occupancy;
            document.getElementById('form-status').value          = status;

            document.getElementById('form-title').textContent     = 'Edit Cottage';
            document.getElementById('form-subtitle').textContent  = 'Update cottage information';
            document.getElementById('form-btn-text').textContent  = 'Update Cottage';

            document.querySelector('.div5').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('cottage-form').reset();
            document.getElementById('form-action').value          = 'create_cottage';
            document.getElementById('form-cottage-id').value      = '';
            document.getElementById('form-title').textContent     = 'Add Cottage';
            document.getElementById('form-subtitle').textContent  = 'Fill in cottage information';
            document.getElementById('form-btn-text').textContent  = 'Save Cottage';

            document.getElementById('cottage-form-wrapper').style.display = 'none';
            document.getElementById('empty-state').style.display  = 'flex';
        }
    </script>
</body>
</html>