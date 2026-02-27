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
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($name === '' || $email === '') {
                $message = 'Name and email are required for guest.';
            } else {
                $guestId = $guestModel->create(['name' => $name, 'email' => $email, 'phone' => $phone]);
                $message = "Guest created (ID: {$guestId}).";
            }
        }

        if (isset($_POST['delete_guest']) && !empty($_POST['guest_id'])) {
            $id = (int)$_POST['guest_id'];
            $guestModel->delete($id);
            $message = "Guest {$id} deleted.";
        }

        if (isset($_POST['create_room'])) {
            $room_number = trim($_POST['room_number'] ?? '');
            $room_type = trim($_POST['room_type'] ?? '');
            $price = $_POST['price_per_night'] ?? 0;
            $status = $_POST['status'] ?? 'available';
            $number_of_beds = isset($_POST['number_of_beds']) ? (int)$_POST['number_of_beds'] : 1;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

            if ($room_number === '' || $room_type === '') {
                $message = 'Room number and type are required.';
            } else {
                $roomId = $roomModel->create([
                    'room_number' => $room_number,
                    'room_type' => $room_type,
                    'price_per_night' => $price,
                    'number_of_beds' => $number_of_beds,
                    'quantity' => $quantity,
                    'status' => $status
                ]);
                $message = "Room created (ID: {$roomId}).";
            }
        }

        if (isset($_POST['delete_room']) && !empty($_POST['room_id'])) {
            $id = (int)$_POST['room_id'];
            $roomModel->delete($id);
            $message = "Room {$id} deleted.";
        }
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
                    <label>Name<br><input name="name" required></label><br>
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
                                <td><?= htmlspecialchars(($g['first_name'] ?? '') . (isset($g['last_name']) && $g['last_name'] !== '' ? ' ' . $g['last_name'] : '')) ?></td>
                                <td><?= htmlspecialchars($g['contact_email'] ?? '') ?></td>
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
                <h2>Rooms</h2>

                <form method="post">
                    <h3>Create Room</h3>
                    <label>Room Number<br><input name="room_number"></label><br>
                    <label>Type<br><input name="room_type" required></label><br>
                    <label>Price<br><input name="price_per_night" type="number" step="0.01" value="0.00"></label><br>
                    <label>Number of Beds<br><input name="number_of_beds" type="number" min="1" value="1"></label><br>
                    <label>Quantity<br><input name="quantity" type="number" min="1" value="1"></label><br>
                    <label>Status<br>
                        <select name="status">
                            <option value="available">available</option>
                            <option value="occupied">occupied</option>
                            <option value="maintenance">maintenance</option>
                        </select>
                    </label><br>
                    <button type="submit" name="create_room">Create Room</button>
                </form>

                <h3>List of Rooms</h3>
                <table>
                    <thead><tr><th>ID</th><th>Number</th><th>Type</th><th>Price</th><th>Beds</th><th>Quantity</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($rooms as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['cottage_id'] ?? $r['room_id']) ?></td>
                                <td><?= htmlspecialchars($r['cottage_number'] ?? $r['room_number']) ?></td>
                                <td><?= htmlspecialchars($r['name'] ?? $r['room_type']) ?></td>
                                <td><?= htmlspecialchars($r['base_price'] ?? $r['price_per_night']) ?></td>
                                <td><?= htmlspecialchars($r['max_occupancy'] ?? $r['number_of_beds'] ?? 0) ?></td>
                                <td><?= htmlspecialchars(isset($r['is_available']) ? ($r['is_available'] ? 'available' : 'occupied') : ($r['status'] ?? '')) ?></td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="room_id" value="<?= (int)($r['cottage_id'] ?? $r['room_id']) ?>">
                                        <button type="submit" name="delete_room" onclick="return confirm('Delete room?')">Delete</button>
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
