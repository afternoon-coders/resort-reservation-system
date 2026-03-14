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

        admin_redirect_to_page('customers');
    }

    $flash = admin_pop_flash();
    if ($flash !== null) {
        if (($flash['type'] ?? '') === 'error') {
            $error = $flash['message'];
        } else {
            $message = $flash['message'];
        }
    }
    

    $recentUsers = $pdo->query(
        'SELECT u.user_id, u.username, g.first_name, g.last_name, u.account_email, u.role 
        FROM Users u
        LEFT JOIN Guests g ON u.guest_id = g.guest_id
        WHERE u.role = "guest"
        ORDER BY u.user_id DESC 
        LIMIT 8'
    )->fetchAll();


} catch (Throwable $e) {
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


    <div class="admin-header">
        <h1>Customers</h1>
        <p>View and manage customer information</p>
    </div>
    <div class="search-section">
        <div class="search-wrapper">
        <input type="text" placeholder="Search customers..." class="search-input">
    </div>
    </div>
    

    <?php if ($error): ?>
        <div class="error-box">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div style="padding:12px;background:#e7f7ed;border:1px solid #b8e0c2;color:#124b26;border-radius:4px;margin-bottom:12px;"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="customers-grid">
        <?php if (empty($recentUsers)): ?>
            <div class="muted">No customers found.</div>
        <?php else: ?>
            <?php foreach ($recentUsers as $u): ?>
                <?php
                    $fullName = trim(implode(' ', array_filter([
                        $u['first_name'] ?? '',
                        $u['last_name'] ?? ''
                    ])));

                    if (empty($fullName)) {
                        $fullName = $u['username'];
                    }

                    $initials = '';
                    if (!empty($u['first_name'])) {
                        $initials .= substr($u['first_name'], 0, 1);
                        if (!empty($u['last_name'])) {
                            $initials .= substr($u['last_name'], 0, 1);
                        }
                    } else {
                        $initials = substr($u['username'], 0, 1);
                    }
                    $initials = strtoupper($initials);
                ?>
                <div class="customer-card">
                    <div class="card-top">
                        <div class="avatar">
                            <?php echo $initials ?: 'U'; ?>
                        </div>

                        <button class="view-btn">View</button>
                    </div>

                    <h3><?php echo htmlspecialchars($fullName); ?></h3>

                    <div class="customer-info">
                        <div class="info-row">
                            <img src="/static/img/icons/mail.svg" class="icon" alt="email icon">
                            <span><?php echo htmlspecialchars($u['account_email'] ?? ''); ?></span>
                        </div>


                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


</body>
</html>
