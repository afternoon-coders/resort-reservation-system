<?php
require_once '../auth/auth_functions.php';
require_once '../helpers/DB.php';

requireLogin();
requireAdmin();

$error = null;
$message = '';
try {
    $pdo = DB::getPDO();

    // Stats Calculation
    $roomsTotal = $pdo->query("SELECT COUNT(*) FROM Cottages")->fetchColumn();
    $roomsAvailable = $pdo->query("SELECT COUNT(*) FROM Cottages WHERE status = 'Available'")->fetchColumn();
    $reservationsTotal = $pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
    $reservationsPending = $pdo->query("SELECT COUNT(*) FROM Reservations WHERE status = 'Pending'")->fetchColumn();
    $usersTotal = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
    $paymentsTotal = $pdo->query("SELECT SUM(amount_paid) FROM Payments")->fetchColumn() ?: 0.0;

    // Recent reservations
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
        
        <h1>Reports</h1>
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
            <h3>Revenue Trend</h3>
        </div>

        <div style="margin-top:20px;" class="card">
            <h3>Top Performing Rooms</h3>
        </div>

</body>
</html>
