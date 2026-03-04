<?php
require_once '../auth/auth_functions.php';
require_once '../helpers/DB.php';

requireLogin();
requireAdmin();

$error = null;
$message = '';
try {
    $pdo = DB::getPDO();

    // Handle admin actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'update_reservation_status' && !empty($_POST['reservation_id']) && isset($_POST['status'])) {
            $stmt = $pdo->prepare('UPDATE Reservations SET status = :s WHERE reservation_id = :id');
            $stmt->execute([':s' => $_POST['status'], ':id' => (int)$_POST['reservation_id']]);
            $message = 'Reservation status updated.';
        }

        if ($action === 'delete_reservation' && !empty($_POST['reservation_id'])) {
            $stmt = $pdo->prepare('DELETE FROM Reservations WHERE reservation_id = :id');
            $stmt->execute([':id' => (int)$_POST['reservation_id']]);
            $message = 'Reservation deleted.';
        }

        if ($action === 'delete_user' && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare('DELETE FROM Users WHERE user_id = :id');
            $stmt->execute([':id' => (int)$_POST['user_id']]);
            $message = 'User deleted.';
        }

        if ($action === 'update_room_status' && !empty($_POST['room_id']) && isset($_POST['status'])) {
            // Map room status to is_available for Cottages
            $isAvail = (strtolower($_POST['status']) === 'available') ? 1 : 0;
            $stmt = $pdo->prepare('UPDATE Cottages SET is_available = :avail WHERE cottage_id = :id');
            $stmt->execute([':avail' => $isAvail, ':id' => (int)$_POST['room_id']]);
            $message = 'Cottage availability updated.';
        }
    }

    $recentReservations = $pdo->query(
        "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.status, CONCAT(g.first_name, ' ', g.last_name) AS guest_name, c.cottage_number
         FROM Reservations r
         LEFT JOIN Guests g ON r.guest_id = g.guest_id
         LEFT JOIN Cottages c ON r.cottage_id = c.cottage_id
         ORDER BY r.reservation_id DESC LIMIT 8"
    )->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
    $roomsTotal = $roomsAvailable = $reservationsTotal = $reservationsPending = $usersTotal = 0;
    $paymentsTotal = 0.0;
    $recentReservations = [];
    $recentUsers = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/static/css/style.css">
<title>Content Management</title>
<script>
function showTab(tab) {
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(t => t.style.display = 'none');
    document.getElementById(tab).style.display = 'block';
    const buttons = document.querySelectorAll('.tabs button');
    buttons.forEach(b => b.classList.remove('active'));
    document.querySelector('.tabs button[data-tab="'+tab+'"]').classList.add('active');
}
window.onload = function() {
    showTab('homepage'); // default
}
</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <div class="">
        
        <h1>Reservations</h1>
        <?php if ($error): ?>
            <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;margin-bottom:12px;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div style="margin-top:20px;" class="card">
            <h3>All Reservations</h3>
            <?php if (empty($recentReservations)): ?>
                <div class="muted">No recent reservations.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentReservations as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['reservation_id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($r['guest_name'] ?? '—'); ?>
                                <?php echo htmlspecialchars($r['contact_email'] ?? '—'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($r['cottage_number'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($r['check_in_date']); ?></td>
                            <td><?php echo htmlspecialchars($r['check_out_date']); ?></td>
                            <td>
                                <div class="badge">
                                    <?php echo htmlspecialchars($r['status']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-btn-container">
                                    <form method="post">
                                        <div class="action-btn">
                                            <input type="hidden" name="action" value="update_reservation_status">
                                            <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                            <select name="status" class="badge">
                                                <option class="pending" value="Pending" <?php echo strtolower($r['status'])==='pending' ? 'selected' : '' ?>>Pending</option>
                                                <option class="confirmed" value="Confirmed" <?php echo strtolower($r['status'])==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option class="" value="Checked-In" <?php echo strtolower($r['status'])==='checked-in' || strtolower($r['status'])==='checked_in' ? 'selected' : '' ?>>Checked-In</option>
                                                <option value="Checked-Out" <?php echo strtolower($r['status'])==='checked-out' || strtolower($r['status'])==='checked_out' ? 'selected' : '' ?>>Checked-Out</option>
                                                <option class="cancelled" value="Cancelled" <?php echo strtolower($r['status'])==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <button class="refresh-btn" type="submit">
                                                <img src="/admin/static/img//adminpanel_icons/refresh.svg" alt="">
                                            </button>
                                        </div>
                                    </form>

                                    <form method="post"  onsubmit="return confirm('Delete reservation?');">
                                        <input type="hidden" name="action" value="delete_reservation">
                                        <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                        <button class="delete-btn" type="submit">
                                            <img src="/admin/static/img/adminpanel_icons/delete.svg" alt="">
                                        </button>
                                    </form>
                                </div> 
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
