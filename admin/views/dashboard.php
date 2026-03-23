<?php
require_once __DIR__ . '/../helpers/admin_backend.php';

$error = null;
$message = '';
$csrfToken = '';
try {
    $pdo = admin_bootstrap();
    $csrfToken = admin_get_csrf_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_require_csrf_token($_POST['csrf_token'] ?? null);

        $action = trim((string)($_POST['action'] ?? ''));
        $result = admin_dispatch_action($pdo, $action, $_POST);
        admin_set_flash($result['ok'] ? 'success' : 'error', $result['message']);

        admin_redirect_to_page('dashboard');
    }

    $flash = admin_pop_flash();
    if ($flash !== null) {
        if (($flash['type'] ?? '') === 'error') {
            $error = $flash['message'];
        } else {
            $message = $flash['message'];
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

} catch (Throwable $e) {
    $error = $e->getMessage();
    $roomsTotal = 0;
    $monthlyReservations = 0;
    $monthlyGuests = 0;
    $currentMonthRevenue = 0.0;
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
            <p>Welcome back! Here is your resort overview.</p>
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
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1a73e8"><path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Z"/></svg>
                </div>
            </div>
            <div class="card-stat">
                <h2><?php echo $roomsTotal; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Cottages</div>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5be288"><path d="M80-200v-240q0-27 11-49t29-39v-112q0-50 35-85t85-35h160q23 0 43 8.5t37 23.5q17-15 37-23.5t43-8.5h160q50 0 85 35t35 85v112q18 17 29 39t11 49v240h-80v-80H160v80H80Zm440-360h240v-80q0-17-11.5-28.5T720-680H560q-17 0-28.5 11.5T520-640v80Zm-320 0h240v-80q0-17-11.5-28.5T400-680H240q-17 0-28.5 11.5T200-640v80Zm-40 200h640v-80q0-17-11.5-28.5T760-480H200q-17 0-28.5 11.5T160-440v80Zm640 0H160h640Z"/></svg>
                </div>
            </div>
            <div class="card-stat">
                <h2><?php echo $monthlyGuests; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Guests Booked This Month</div>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5be288"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm466 0q-47 47-113 47-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-8１T5４４−７９２ｑ１４−５　２８−６．５ｔ２８−１．５ｑ６６　０　１１３　４７ｔ４７　１１３ｑ０　６６−４７　１１３ＺＭ１２０−２４０ｈ４８０ｖ−３２ｑ０−１１−５．５−２０Ｔ５８０−３０６ｑ−５４−２７−１０９−４０．５Ｔ３６０−３６０ｑ−５６　０−１１１　１３．５Ｔ１４０−３０６ｑ−９　５−１４．５　１４ｔ−５．５　２０ｖ３２Ｚｍ２９６．５−３４３．５Ｑ４４０−６０７　４４０−６４０ｔ−２３．５−５６．５Ｑ３９３−７２０　３６０−７２０ｔ−５６．５　２３．５Ｑ２８０−６７３　２８₀−６４₀ｔ２₃．₅　₅₆．₅Ｑ₃₂₇−₅₆₀　₃₆₀−₅₆₀ｔ₅₆．₅−₂₃．₅ＺＭ³⁶⁰−₂⁴⁰Ｚｍ⁰−⁴⁰⁰Ｚ"/></svg>
                </div>
            </div>
            <div class="card-stat">
                <h2>&#8369; <?php echo number_format($currentMonthRevenue, 2); ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Current Month Revenue</div>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1a73e8"><path d="M200-120q-33 0-56.5-23.5T120-200v-640h80v640h640v80H200Zm40-120v-360h160v360H240Zm200 0v-560h160v560H440Zm200 0v-200h160v200H640Z"/></svg>
                </div>
            </div>
        </div>

        <div style="margin-top:20px;" class="card">
            <div class="row">
                <h3>Recent Reservations</h3>
                <button class="refresh-btn" type="submit" style="margin-left: auto; margin-right: 5px;">
                    <img src="/admin/static/img//adminpanel_icons/refresh.svg" alt="">
                    update
                </button>
            </div>
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
                                    <form method="post">
                                        <div class="action-btn">
                                            <input type="hidden" name="action" value="update_reservation_status" onchange="updateSelectClass(this)">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                            
                                        </div>
                                    </form>

                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete reservation?');">
                                        <input type="hidden" name="action" value="delete_reservation">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                        <button class="delete-btn" type="submit">
                                            <img src="/admin/static/img//adminpanel_icons/delete.svg" alt="">
                                            delete
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
