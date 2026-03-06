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
            $stmt = $pdo->prepare('UPDATE Cottages SET status = :s WHERE cottage_id = :id');
            $stmt->execute([':s' => $_POST['status'], ':id' => (int)$_POST['room_id']]);
            $message = 'Cottage status updated.';
        }
    }
    
    
    // Calculate Stats
    $roomsTotal = $pdo->query("SELECT COUNT(*) FROM Cottages")->fetchColumn();
    $roomsAvailable = $pdo->query("SELECT COUNT(*) FROM Cottages WHERE status = 'Available'")->fetchColumn();
    $reservationsTotal = $pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
    $reservationsPending = $pdo->query("SELECT COUNT(*) FROM Reservations WHERE status = 'Pending'")->fetchColumn();
    $usersTotal = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
    $paymentsTotal = $pdo->query("SELECT SUM(amount_paid) FROM Payments")->fetchColumn() ?: 0.0;

    // Get current month revenue
    $monthlyRevenue = $pdo->query(
        "SELECT SUM(amount_paid) AS revenue
        FROM Payments
        WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
        AND YEAR(payment_date) = YEAR(CURRENT_DATE())"
    )->fetch();

    // Recent reservations - Using GROUP_CONCAT for multiple cottages
    $recentReservations = $pdo->query(
        "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.status, 
                CONCAT(COALESCE(g.first_name,''), ' ', COALESCE(g.last_name,'')) AS guest_name, 
                GROUP_CONCAT(c.cottage_number SEPARATOR ', ') as cottage_number
         FROM Reservations r
         LEFT JOIN Guests g ON r.guest_id = g.guest_id
         LEFT JOIN Reservation_Items ri ON r.reservation_id = ri.reservation_id
         LEFT JOIN Cottages c ON ri.cottage_id = c.cottage_id
         GROUP BY r.reservation_id
         ORDER BY r.reservation_id DESC LIMIT 8"
    )->fetchAll();

    // Recent users - Joining with Guests
    $recentUsers = $pdo->query(
        "SELECT u.user_id, u.username, g.first_name, g.last_name, g.email as account_email, u.role 
         FROM Users u 
         LEFT JOIN Guests g ON u.guest_id = g.guest_id
         ORDER BY u.user_id DESC LIMIT 8"
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
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
        </div>
        <?php if ($error): ?>
            <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;margin-bottom:12px;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="grid">
            <div class="card-stat">
                <h2><?php echo $reservationsTotal; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Reservations</div>
                    <img src="static/img/adminpanel_icons/reservation.svg" alt="">
                </div>
            </div>
            <div class="card-stat">
                    <h2><?php echo ($roomsTotal-$reservationsTotal) . " / " . ($roomsAvailable); ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Cottages</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
            <div class="card-stat">
                    <h2><?php echo $usersTotal; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Guests</div>
                    <img src="static/img/adminpanel_icons/people.svg" alt="">
                </div>
            </div>
            <div class="card-stat">
                    <h2>&#8369; <?php echo $monthlyRevenue['revenue']; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Monthly Revenue</div>
                    <img src="static/img/adminpanel_icons/dollar.svg" alt="">
                </div>
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
                                <div class="action-btn-container">
                                    <form method="post" style="display:inline-block;margin-right:6px;">
                                        <div class="action-btn">
                                            <input type="hidden" name="action" value="update_reservation_status">
                                            <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                            <select name="status" class="badge">
                                                <option value="Pending" <?php echo strtolower($r['status'])==='pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Confirmed" <?php echo strtolower($r['status'])==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="Checked-In" <?php echo strtolower($r['status'])==='checked-in' || strtolower($r['status'])==='checked_in' ? 'selected' : '' ?>>Checked-In</option>
                                                <option value="Checked-Out" <?php echo strtolower($r['status'])==='checked-out' || strtolower($r['status'])==='checked_out' ? 'selected' : '' ?>>Checked-Out</option>
                                                <option value="Cancelled" <?php echo strtolower($r['status'])==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <button class="refresh-btn" type="submit">
                                                <img src="/admin/static/img//adminpanel_icons/refresh.svg" alt="">
                                            </button>
                                        </div>
                                    </form>

                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete reservation?');">
                                        <input type="hidden" name="action" value="delete_reservation">
                                        <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                        <button class="delete-btn" type="submit">
                                            <img src="/admin/static/img//adminpanel_icons/delete.svg" alt="">
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

</body>
</html>
