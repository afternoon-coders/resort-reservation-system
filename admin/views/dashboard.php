<?php
require_once '../auth/auth_functions.php';
require_once '../helpers/DB.php';
require_once '../inc/csrf.php';

requireLogin();
requireAdmin();

$error = null;
$message = '';
$csrfToken = '';
try {
    $pdo = DB::getPDO();

    // Handle admin actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
        csrf_verify_or_die();
        $action = $_POST['action'];

        if ($action === 'update_reservation_status' && !empty($_POST['reservation_id']) && isset($_POST['status'])) {
            $allowedStatuses = ['Pending', 'Confirmed', 'Checked-In', 'Checked-Out', 'Cancelled'];
            if (!in_array($_POST['status'], $allowedStatuses)) {
                $message = 'Invalid status value.';
            } else {
                $stmt = $pdo->prepare('UPDATE Reservations SET status = :s WHERE reservation_id = :id');
                $stmt->execute([':s' => $_POST['status'], ':id' => (int)$_POST['reservation_id']]);
                $message = 'Reservation status updated.';
            }
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
            $allowedRoomStatuses = ['Available', 'Occupied', 'Maintenance'];
            if (!in_array($_POST['status'], $allowedRoomStatuses)) {
                $message = 'Invalid room status value.';
            } else {
                $stmt = $pdo->prepare('UPDATE Cottages SET status = :s WHERE cottage_id = :id');
                $stmt->execute([':s' => $_POST['status'], ':id' => (int)$_POST['room_id']]);
                $message = 'Cottage status updated.';
            }
        }
    }
    
    
    // Monthly report stats
    $roomsTotal = (int)$pdo->query("SELECT COUNT(*) FROM Cottages")->fetchColumn();

    $monthlyReservations = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM Reservations
         WHERE MONTH(booking_date) = MONTH(CURRENT_DATE())
         AND YEAR(booking_date) = YEAR(CURRENT_DATE())"
    )->fetchColumn();

    $monthlyGuests = (int)$pdo->query(
        "SELECT COUNT(DISTINCT guest_id)
         FROM Reservations
         WHERE MONTH(booking_date) = MONTH(CURRENT_DATE())
         AND YEAR(booking_date) = YEAR(CURRENT_DATE())"
    )->fetchColumn();

    $currentMonthRevenue = (float)($pdo->query(
        "SELECT COALESCE(SUM(amount_paid), 0)
         FROM Payments
         WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
         AND YEAR(payment_date) = YEAR(CURRENT_DATE())"
    )->fetchColumn() ?: 0);

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
    error_log('Dashboard error: ' . $e->getMessage());
    $error = 'An error occurred loading the dashboard.';
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
        <?php if ($message): ?>
            <div style="padding:12px;background:#e7f7ed;border:1px solid #b8e0c2;color:#124b26;border-radius:4px;margin-bottom:12px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="grid">
            <div class="card-stat">
                <h2><?php echo $monthlyReservations; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Monthly Reservations</div>
                    <img src="static/img/adminpanel_icons/reservation.svg" alt="">
                </div>
            </div>
            <div class="card-stat">
                    <h2><?php echo $roomsTotal; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Cottages</div>
                    <img src="static/img/adminpanel_icons/bed.svg" alt="">
                </div>
            </div>
            <div class="card-stat">
                    <h2><?php echo $monthlyGuests; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Guests Booked This Month</div>
                    <img src="static/img/adminpanel_icons/people.svg" alt="">
                </div>
            </div>
            <div class="card-stat">
                    <h2>&#8369; <?php echo htmlspecialchars(number_format((float)($monthlyRevenue['revenue'] ?? 0), 2)); ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Current Month Revenue</div>
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
                            <td>
                                <form method="post" style="display:inline-block;margin-right:6px;">
                                    <div class="action-btn">
                                        <select name="status" class="badge" onchange="updateSelectClass(this)">
                                            <option value="Pending" class="pending" <?php echo strtolower($r['status'])==='pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" class="confirm" <?php echo strtolower($r['status'])==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="Checked-In" class="check-in" <?php echo strtolower($r['status'])==='checked-in' || strtolower($r['status'])==='checked_in' ? 'selected' : '' ?>>Checked-In</option>
                                            <option value="Checked-Out" class="check-out" <?php echo strtolower($r['status'])==='checked-out' || strtolower($r['status'])==='checked_out' ? 'selected' : '' ?>>Checked-Out</option>
                                            <option value="Cancelled" class="cancel" <?php echo strtolower($r['status'])==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <div class="action-btn-container">
                                    <form method="post" style="display:inline-block;margin-right:6px;">
                                        <?php echo csrf_field(); ?>
                                        <div class="action-btn">
                                            <input type="hidden" name="action" value="update_reservation_status" onchange="updateSelectClass(this)">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                            <button class="refresh-btn" type="submit">
                                                <img src="/admin/static/img//adminpanel_icons/refresh.svg" alt="">
                                            </button>
                                        </div>
                                    </form>

                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete reservation?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_reservation">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
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
        
        <script>
            document.querySelectorAll('select[name="status"]').forEach(select => {
                updateSelectClass(select);
            });

            function updateSelectClass(select) {
                select.classList.remove('pending', 'confirm', 'check-in', 'check-out', 'cancel');
                const selectedOption = select.options[select.selectedIndex];
                select.classList.add(selectedOption.className);
            }
        </script>
</body>
</html>
