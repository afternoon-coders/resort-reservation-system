<?php
// admin/helpers_demo.php

require_once __DIR__ . '/../helpers/GuestModel.php';
require_once __DIR__ . '/../helpers/RoomModel.php';
// header/footer are optional; they exist but are empty in this project
@include_once __DIR__ . '/../inc/header.php';

$message = '';

try {
    $guestModel = new GuestModel();
    $roomModel = new RoomModel();

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create_guest'])) {
            $fName = trim($_POST['first_name'] ?? '');
            $lName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($fName === '' || $email === '') {
                $message = 'First name and email are required for guest.';
            } else {
                $guestId = $guestModel->create(['first_name' => $fName, 'last_name' => $lName, 'email' => $email, 'phone' => $phone, 'address' => 'Demo Address']);
                $message = "Guest created (ID: {$guestId}).";
            }
        }

        if (isset($_POST['delete_guest']) && !empty($_POST['guest_id'])) {
            $id = (int)$_POST['guest_id'];
            $guestModel->delete($id);
            $message = "Guest {$id} deleted.";
        }

        if (isset($_POST['create_room'])) {
            $room_number = trim($_POST['cottage_number'] ?? '');
            $type_id = (int)($_POST['type_id'] ?? 1);
            $price = $_POST['base_price'] ?? 0;
            $status = $_POST['status'] ?? 'Available';
            $max_occupancy = isset($_POST['max_occupancy']) ? (int)$_POST['max_occupancy'] : 2;

            if ($room_number === '') {
                $message = 'Cottage number is required.';
            } else {
                $roomId = $roomModel->create([
                    'room_number' => $room_number,
                    'type_id' => $type_id,
                    'price_per_night' => $price,
                    'max_occupancy' => $max_occupancy,
                    'status' => $status
                ]);
                $message = "Cottage created (ID: {$roomId}).";
            }
        }
// ... (omitting delete_room logic as it's fine)
    }

    // Fetch lists
    $guests = $guestModel->getAll(['limit' => 100]);
    $rooms = $roomModel->getAll(['limit' => 100]);

} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
    $guests = [];
    $rooms = [];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Database Demo - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .col { float:left; width:48%; margin-right:2%; }
        .card { border:1px solid #ddd; padding:12px; margin-bottom:16px; }
        table { width:100%; border-collapse:collapse; }
        th,td { border:1px solid #eee; padding:8px; text-align:left; }
        .clearfix:after { content:""; display:table; clear:both; }
        .msg { padding:10px; background:#f0f8ff; border:1px solid #cce; margin-bottom:12px; }
    </style>
</head>
<body>
    <h1>Admin — Database Demo</h1>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="clearfix">
        <div class="col">
            <div class="card">
                <h2>Guests</h2>

                <form method="post">
                    <h3>Create Guest</h3>
                    <label>First Name<br><input name="first_name" required></label><br>
                    <label>Last Name<br><input name="last_name"></label><br>
                    <label>Email<br><input name="email" type="email" required></label><br>
                    <label>Phone<br><input name="phone"></label><br>
                    <button type="submit" name="create_guest">Create Guest</button>
                </form>

                <h3>List of Guests</h3>
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($guests as $g): ?>
                            <tr>
                                <td><?= htmlspecialchars($g['guest_id']) ?></td>
                                <td><?= htmlspecialchars($g['first_name'] . ' ' . ($g['last_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($g['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($g['phone_number'] ?? '') ?></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="guest_id" value="<?= (int)$g['guest_id'] ?>">
                                        <button type="submit" name="delete_guest" onclick="return confirm('Delete guest?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col">
            <div class="card">
                <h2>Cottages</h2>

                <form method="post">
                    <h3>Create Cottage</h3>
                    <label>Cottage Number<br><input name="cottage_number"></label><br>
                    <label>Type ID<br><input name="type_id" type="number" value="1"></label><br>
                    <label>Base Price<br><input name="base_price" type="number" step="0.01" value="0.00"></label><br>
                    <label>Max Occupancy<br><input name="max_occupancy" type="number" min="1" value="2"></label><br>
                    <label>Status<br>
                        <select name="status">
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </label><br>
                    <button type="submit" name="create_room">Create Cottage</button>
                </form>

                <h3>List of Cottages</h3>
                <table>
                    <thead><tr><th>ID</th><th>Number</th><th>Type</th><th>Price</th><th>Max Occ</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($rooms as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['cottage_id']) ?></td>
                                <td><?= htmlspecialchars($r['cottage_number']) ?></td>
                                <td><?= htmlspecialchars($r['name'] ?? 'Undefined') ?></td>
                                <td><?= htmlspecialchars($r['base_price']) ?></td>
                                <td><?= htmlspecialchars($r['max_occupancy']) ?></td>
                                <td><?= htmlspecialchars($r['status']) ?></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="room_id" value="<?= (int)$r['cottage_id'] ?>">
                                        <button type="submit" name="delete_room" onclick="return confirm('Delete cottage?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php @include_once __DIR__ . '/../inc/footer.php'; ?>
</body>
</html>
