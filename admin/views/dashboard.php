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

    // Stats
    $roomsTotal = (int)$pdo->query('SELECT COUNT(*) FROM Cottages')->fetchColumn();
    $roomsAvailable = (int)$pdo->query('SELECT COUNT(*) FROM Cottages WHERE is_available = 1')->fetchColumn();

    $reservationsTotal = (int)$pdo->query('SELECT COUNT(*) FROM Reservations')->fetchColumn();
    $reservationsPending = (int)$pdo->query("SELECT COUNT(*) FROM Reservations WHERE LOWER(status) = 'pending'")->fetchColumn();

    $usersTotal = (int)$pdo->query('SELECT COUNT(*) FROM Users')->fetchColumn();

    $paymentsTotal = $pdo->query('SELECT IFNULL(SUM(amount_paid),0) FROM Payments')->fetchColumn();

    $recentReservations = $pdo->query(
        "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.status, CONCAT(g.first_name, ' ', g.last_name) AS guest_name, c.cottage_number
         FROM Reservations r
         LEFT JOIN Guests g ON r.guest_id = g.guest_id
         LEFT JOIN Cottages c ON r.cottage_id = c.cottage_id
         ORDER BY r.reservation_id DESC LIMIT 8"
    )->fetchAll();

    $recentUsers = $pdo->query('SELECT user_id, username, account_email, role FROM Users ORDER BY user_id DESC LIMIT 8')->fetchAll();

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
    <div class=""></div>
        
        <h1>Admin Dashboard</h1>
        <?php if ($error): ?>
            <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;margin-bottom:12px;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="grid">
            <div class="card-stat">
                <h2><?php echo $roomsTotal; ?></h2>
                <div class="muted">Total Cottages</div>
            </div>
            <div class="card-stat">
                <h2><?php echo $roomsAvailable; ?></h2>
                <div class="muted">Available Cottages</div>
            </div>
            <div class="card-stat">
                <h2><?php echo $reservationsTotal; ?></h2>
                <div class="muted">Total Reservations</div>
            </div>
            <div class="card-stat">
                <h2><?php echo $reservationsPending; ?></h2>
                <div class="muted">Pending Reservations</div>
            </div>
            <div class="card-stat">
                <h2><?php echo $usersTotal; ?></h2>
                <div class="muted">Registered Users</div>
            </div>
            <div class="card-stat">
                <h2>&#8369; <?php echo number_format((float)$paymentsTotal,2); ?></h2>
                <div class="muted">Total Payments</div>
            </div>
        </div>

        <div style="margin-top:20px;" class="card">
            <h3>Recent Reservations</h3>
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
                            <td><?php echo htmlspecialchars($r['guest_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($r['cottage_number'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($r['check_in_date']); ?></td>
                            <td><?php echo htmlspecialchars($r['check_out_date']); ?></td>
                            <td><?php echo htmlspecialchars($r['status']); ?></td>
                            <td>
                                <form method="post" style="display:inline-block;margin-right:6px;">
                                    <input type="hidden" name="action" value="update_reservation_status">
                                    <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                    <select name="status">
                                        <option value="Pending" <?php echo strtolower($r['status'])==='pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Confirmed" <?php echo strtolower($r['status'])==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="Checked-In" <?php echo strtolower($r['status'])==='checked-in' || strtolower($r['status'])==='checked_in' ? 'selected' : '' ?>>Checked-In</option>
                                        <option value="Checked-Out" <?php echo strtolower($r['status'])==='checked-out' || strtolower($r['status'])==='checked_out' ? 'selected' : '' ?>>Checked-Out</option>
                                        <option value="Cancelled" <?php echo strtolower($r['status'])==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit">Update</button>
                                </form>

                                <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete reservation?');">
                                    <input type="hidden" name="action" value="delete_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-top:20px;" class="card">
            <h3>Recent Users</h3>
            <?php if (empty($recentUsers)): ?>
                <div class="muted">No users found.</div>
            <?php else: ?>
                <table>
                    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?php echo (int)$u['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['account_email'] ?? $u['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($u['role'] ?? 'guest'); ?></td>
                            <td>
                                <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete user?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-top:20px;" class="card">
            <h3>Rooms</h3>
            <p class="muted">Quick links to manage rooms.</p>
            <p><a href="index.php?page=manage_rooms">Open Manage Rooms</a></p>
        </div>

</body>
</html>
