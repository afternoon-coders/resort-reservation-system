<?php
require_once __DIR__ . '/../auth/auth_functions.php';
require_once __DIR__ . '/../helpers/DB.php';

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
            $stmt = $pdo->prepare('UPDATE reservations SET status = :s WHERE reservation_id = :id');
            $stmt->execute([':s' => $_POST['status'], ':id' => (int)$_POST['reservation_id']]);
            $message = 'Reservation status updated.';
        }

        if ($action === 'delete_reservation' && !empty($_POST['reservation_id'])) {
            $stmt = $pdo->prepare('DELETE FROM reservations WHERE reservation_id = :id');
            $stmt->execute([':id' => (int)$_POST['reservation_id']]);
            $message = 'Reservation deleted.';
        }

        if ($action === 'delete_user' && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :id');
            $stmt->execute([':id' => (int)$_POST['user_id']]);
            $message = 'User deleted.';
        }

        if ($action === 'update_room_status' && !empty($_POST['room_id']) && isset($_POST['status'])) {
            $stmt = $pdo->prepare('UPDATE rooms SET status = :s WHERE room_id = :id');
            $stmt->execute([':s' => $_POST['status'], ':id' => (int)$_POST['room_id']]);
            $message = 'Room status updated.';
        }
    }

    // Stats
    $roomsTotal = (int)$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    $roomsAvailable = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();

    $reservationsTotal = (int)$pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
    $reservationsPending = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();

    $usersTotal = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $paymentsTotal = $pdo->query('SELECT IFNULL(SUM(amount),0) FROM payments')->fetchColumn();

    $recentReservations = $pdo->query(
        "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.status, g.name AS guest_name, rm.room_number
         FROM reservations r
         LEFT JOIN guests g ON r.guest_id = g.guest_id
         LEFT JOIN rooms rm ON r.room_id = rm.room_id
         ORDER BY r.reservation_id DESC LIMIT 8"
    )->fetchAll();

    $recentUsers = $pdo->query('SELECT user_id, username, email, role FROM users ORDER BY user_id DESC LIMIT 8')->fetchAll();

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
<title>Content Management</title>
<style>
    body { 
        font-family: Arial, sans-serif; 
        background:#f5f5f5; 
        margin:0; 
        padding:0;
    }
    .container { 
        max-width: 1200px; 
        margin:auto; 
        padding:20px; 
    }
    h1, h2, h3 { margin:0; }
    .card { background:#fff; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
    .card-header { display:flex; justify-content: space-between; align-items:center; }
    .card-header button { background:none; border:none; color: #007BFF; cursor:pointer; }
    input, textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:4px; }
    label { font-weight:bold; }
    button.save-btn { padding:8px 16px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer; }
    button.save-btn:hover { background:#218838; }
    .tabs { display:flex; gap:10px; margin-bottom:20px; flex-wrap: wrap;}
    .tabs button { padding:8px 16px; border:none; border-radius:4px; cursor:pointer; background:#ddd; }
    .tabs button.active { background:#007BFF; color:#fff; }
    .message { padding:10px; background:#d1e7dd; color:#0f5132; margin-bottom:20px; border-radius:4px; }
</style>
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
    <style>
        body { font-family: Arial, sans-serif; margin:20px; }
        .grid { display:flex; gap:16px; flex-wrap:wrap; }
        .card { background:#fff; border:1px solid #e6e6e6; padding:16px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        .card.stat { flex:1 1 200px; }
        h1 { margin:0 0 12px 0; }
        table { width:100%; border-collapse:collapse; }
        th,td { text-align:left; padding:8px; border-bottom:1px solid #f0f0f0; }
        .muted { color:#666; font-size:0.95rem; }
    </style>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <?php if ($error): ?>
        <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;margin-bottom:12px;">Error: <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card stat">
            <h2><?php echo $roomsTotal; ?></h2>
            <div class="muted">Total Rooms</div>
        </div>
        <div class="card stat">
            <h2><?php echo $roomsAvailable; ?></h2>
            <div class="muted">Available Rooms</div>
        </div>
        <div class="card stat">
            <h2><?php echo $reservationsTotal; ?></h2>
            <div class="muted">Total Reservations</div>
        </div>
        <div class="card stat">
            <h2><?php echo $reservationsPending; ?></h2>
            <div class="muted">Pending Reservations</div>
        </div>
        <div class="card stat">
            <h2><?php echo $usersTotal; ?></h2>
            <div class="muted">Registered Users</div>
        </div>
        <div class="card stat">
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
                        <td><?php echo htmlspecialchars($r['room_number'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($r['check_in_date']); ?></td>
                        <td><?php echo htmlspecialchars($r['check_out_date']); ?></td>
                        <td><?php echo htmlspecialchars($r['status']); ?></td>
                        <td>
                            <form method="post" style="display:inline-block;margin-right:6px;">
                                <input type="hidden" name="action" value="update_reservation_status">
                                <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                <select name="status">
                                    <option value="pending" <?php echo $r['status']==='pending' ? 'selected' : '' ?>>pending</option>
                                    <option value="confirmed" <?php echo $r['status']==='confirmed' ? 'selected' : '' ?>>confirmed</option>
                                    <option value="checked_in" <?php echo $r['status']==='checked_in' ? 'selected' : '' ?>>checked_in</option>
                                    <option value="cancelled" <?php echo $r['status']==='cancelled' ? 'selected' : '' ?>>cancelled</option>
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
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['role']); ?></td>
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
