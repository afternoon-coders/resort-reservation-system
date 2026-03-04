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

        if ($action === 'delete_user' && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare('DELETE FROM Users WHERE user_id = :id');
            $stmt->execute([':id' => (int)$_POST['user_id']]);
            $message = 'User deleted.';
        }

    }
    

    $recentUsers = $pdo->query(
        'SELECT user_id, username, first_name, middle_name, last_name, account_email, role 
        FROM Users 
        WHERE role = "admin"
        ORDER BY user_id DESC 
        LIMIT 8'
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
        
        <?php if ($error): ?>
            <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;margin-bottom:12px;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div style="margin-top:20px;" class="">
            <h3>Recent Reservations</h3>
            <div style="margin-top:20px;" class="card">
                <h3>Recent Users</h3>
                <?php if (empty($recentUsers)): ?>
                    <div class="muted">No users found.</div>
                <?php else: ?>
                    <table>
                        <thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td><?php echo (int)$u['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars(trim(implode(' ', array_filter([$u['first_name'], $u['middle_name'], $u['last_name']])))); ?></td>
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
        </div>
    </div>

</body>
</html>
