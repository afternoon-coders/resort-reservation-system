<?php
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

    // Calculate stats
    $roomsTotal = $pdo->query("SELECT COUNT(*) FROM Cottages")->fetchColumn();
    $roomsAvailable = $pdo->query("SELECT COUNT(*) FROM Cottages WHERE status = 'Available'")->fetchColumn();
    $reservationsTotal = $pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
    $cottageTypes = $pdo->query("SELECT COUNT(*) FROM Cottage_Types")->fetchColumn();

    $types = $pdo->query("SELECT type_id, type_name AS name FROM Cottage_Types ORDER BY type_name ASC")->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
    $rooms = [];
    $types = [];
    $roomsTotal = 0;
    $roomsAvailable = 0;
    $reservationsTotal = 0;
    $cottageTypes = 0;
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
        <h1>Manage Cottages</h1>
    </div>

    <div class="manage-room-parent">
        <div class="div1 div-cards">
            <div class="card-stat">
                    <h2><?php echo ($roomsTotal)?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Cottages</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
        </div>

        <div class="div2">
            <div class="card-stat">
                    <h2><?php echo ($roomsAvailable)?></h2>
                <div class="card-stat-content">
                    <div class="muted">Available</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
        </div>

        <div class="div3 div-cards">
            <div class="card-stat">
                <h2><?php echo ($cottageTypes)?></h2>
                <div class="card-stat-content">
                    <div class="muted">Cottage Types</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
        </div>

        <div class="div4">

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <h3>Room Types</h3>
                <p>Click on a room to edit its details</p>

                <?php foreach ($rooms as $r): ?>
                    <div class="cottage-card">
                        <div class="img-container">
                            <span>
                                <img src="" alt="">
                            </span>
                        </div>
                        <div class="cottage-info">
                            <span>
                                <h3><?php echo htmlspecialchars($r['name'] ?? '—'); ?></h3>
                            </span>
                            <span style="display: flex; justify-content: flex-start; width: 100%; margin-bottom: 10px;">
                                <p>Room No: <?php echo htmlspecialchars($r['cottage_number']); ?></p>
                            </span>
                            <div style="display:flex; flex-direction: row; gap: 10px; width: 100%;">
                                <span style="display:flex; flex-direction: row; gap: 15px; width: 150px;">
                                    <p>₱ <?php echo number_format($r['base_price'], 2); ?>/night</p>
                                </span>
                                <span style="display:flex; flex-direction: row; gap: 5px; width: 150px; align-items: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#969696"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm466 0q-47 47-113 47-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113q0 66-47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm296.5-343.5Q440-607 440-640t-23.5-56.5Q393-720 360-720t-56.5 23.5Q280-673 280-640t23.5 56.5Q327-560 360-560t56.5-23.5ZM360-240Zm0-400Z"/></svg>
                                    <p><?php echo htmlspecialchars($r['max_occupancy']); ?></p>
                                </span>
                            </div>
                        </div>
                        <div class="stats-action-container">
                            <span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="cottage_id" value="<?php echo $r['cottage_id']; ?>">
                                    <select name="status" class="badge" onchange="updateSelectClass(this); this.form.submit()">
                                        <option value="Available" <?php echo $r['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="Occupied" <?php echo $r['status'] === 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                                        <option value="Maintenance" <?php echo $r['status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="Out of Order" <?php echo $r['status'] === 'Out of Order' ? 'selected' : ''; ?>>Out of Order</option>
                                    </select>
                                </form>
                            </span>
                            <span>
                                <div class="action-btn-container">
                                    <!-- <a href="index.php?page=edit_room&id=<?php echo $r['cottage_id']; ?>" class="edit-btn">
                                        <img src="static/img/adminpanel_icons/edit.svg" alt="">
                                    </a> -->
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this cottage?');">
                                        <input type="hidden" name="action" value="delete_cottage">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="cottage_id" value="<?php echo $r['cottage_id']; ?>">
                                        <button type="submit" class="delete-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FF2E2E"><path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg>
                                    </form>
                                </div>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>

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
                        <input type="hidden" name="action" value="create_cottage">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <div class="form-group">
                            <label class="edit-cottage-label">Cottage Number *</label>
                            <input type="text" name="cottage_number" class="booknow-input" style="background-color: transparent; border: 1.5px solid #e2e8f0;" required>
                        </div>

                        <div class="form-group">
                            <label class="edit-cottage-label">Cottage Type *</label>
                            <select name="type_id" class="booknow-select" style="background-color: transparent; border: 1.5px solid #e2e8f0;" required>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo $t['type_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                            <label class="edit-cottage-label">Base Price (₱)</label>
                            <input type="number" name="base_price" step="0.01" class="booknow-input" style="background-color: transparent; border: 1.5px solid #e2e8f0;" value="0.00">
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                            <label class="edit-cottage-label">Max Occupancy</label>
                            <input type="number" name="max_occupancy" class="booknow-input" style="background-color: transparent; border: 1.5px solid #e2e8f0;" value="2">
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                            <label class="edit-cottage-label">Status</label>
                            <select name="status" class="booknow-select">
                                <option value="Available">Available</option>
                                <option value="Occupied">Occupied</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Out of Order">Out of Order</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top:15px;">
                            <label class="edit-cottage-label">Description</label>
                            <textarea name="description" class="booknow-textarea"></textarea>
                        </div>

                        <div style="margin-top:20px;">
                            <button type="submit" class="btn btn-primary">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#fff"><path d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM565-275q35-35 35-85t-35-85q-35-35-85-35t-85 35q-35 35-35 85t35 85q35 35 85 35t85-35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"/></svg>
                                </span>
                                Save Cottage
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateSelectClass(select) {
            select.className = 'badge'; // reset first
            if (select.value === 'Available') {
                select.classList.add('badge-available');
            } else if (select.value === 'Occupied') {
                select.classList.add('badge-occupied');
            } else if (select.value === 'Maintenance') {
                select.classList.add('badge-maintenance');
            }
        }

        // Run on page load to set initial color
        document.querySelectorAll('select.badge').forEach(updateSelectClass);
    </script>
</body>
</html>