<?php
require_once __DIR__ . '/../helpers/admin_backend.php';

$error = null;
$message = '';
$csrfToken = '';
$searchTerm = '';
$statusFilter = '';
try {
    $pdo = admin_bootstrap();
    $csrfToken = admin_get_csrf_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_require_csrf_token($_POST['csrf_token'] ?? null);

        $action = trim((string)($_POST['action'] ?? ''));
        $result = admin_dispatch_action($pdo, $action, $_POST);
        admin_set_flash($result['ok'] ? 'success' : 'error', $result['message']);

        admin_redirect_to_page('reservations');
    }

    $flash = admin_pop_flash();
    if ($flash !== null) {
        if (($flash['type'] ?? '') === 'error') {
            $error = $flash['message'];
        } else {
            $message = $flash['message'];
        }
    }

    // Search and filter logic
    $searchTerm = trim((string)($_GET['search'] ?? ''));
    if (strlen($searchTerm) > 100) {
        $searchTerm = substr($searchTerm, 0, 100);
    }

    $normalizedStatus = admin_normalize_enum($_GET['status'] ?? null, admin_reservation_statuses());
    $statusFilter = $normalizedStatus ?? '';

    if (isset($_GET['clear'])) {
        admin_redirect_to_page('reservations');
    }

    $query = "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.status, 
                     CONCAT(COALESCE(g.first_name,''), ' ', COALESCE(g.last_name,'')) AS guest_name, 
                     g.email AS contact_email, GROUP_CONCAT(c.cottage_number SEPARATOR ', ') as cottage_number
              FROM Reservations r
              LEFT JOIN Guests g ON r.guest_id = g.guest_id
              LEFT JOIN Reservation_Items ri ON r.reservation_id = ri.reservation_id
              LEFT JOIN Cottages c ON ri.cottage_id = c.cottage_id";
    
    $where = [];
    $params = [];

    if ($searchTerm) {
        $searchCond = "(g.first_name LIKE :s1 OR g.last_name LIKE :s2 OR g.email LIKE :s3";
        $params[':s1'] = "%$searchTerm%";
        $params[':s2'] = "%$searchTerm%";
        $params[':s3'] = "%$searchTerm%";
        
        // If search term is numeric, also search by reservation ID
        if (is_numeric($searchTerm)) {
            $searchCond .= " OR r.reservation_id = :res_id";
            $params[':res_id'] = (int)$searchTerm;
        }
        $searchCond .= ")";
        $where[] = $searchCond;
    }

    if ($statusFilter) {
        $where[] = "r.status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($where) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $query .= " GROUP BY r.reservation_id ORDER BY r.reservation_id DESC LIMIT 50";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $recentReservations = $stmt->fetchAll();

    // AJAX handler: return only the rows if requested via AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        ob_start();
        if (empty($recentReservations)) {
            echo '<tr><td colspan="7" class="muted">No matching reservations found.</td></tr>';
        } else {
            foreach ($recentReservations as $r) {
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['reservation_id']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($r['guest_name'] ?? '—'); ?>
                        <br><small style="color:#666;"><?php echo htmlspecialchars($r['contact_email'] ?? ''); ?></small>
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
                            <form method="post" >
                                <div class="action-btn">
                                    <input type="hidden" name="action" value="update_reservation_status">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
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
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                <button class="delete-btn" type="submit">
                                    <img src="/admin/static/img/adminpanel_icons/delete.svg" alt="">
                                </button>
                            </form>
                        </div> 
                    </td>
                </tr>
                <?php
            }
        }
        echo ob_get_clean();
        exit;
    }

} catch (Throwable $e) {
    $error = $e->getMessage();
    $recentReservations = [];
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

    // Real-time Search Logic
    let searchTimeout;
    function triggerSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const form = document.getElementById('searchForm');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            
            // Update URL without reload (optional but good for UX)
            // history.replaceState(null, '', '?' + params);

            fetch('?' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('reservationTableBody').innerHTML = html;
            })
            .catch(err => console.error('Search error:', err));
        }, 300); // 300ms debounce
    }

    window.onload = function() {
        showTab('homepage'); // default
        
        const searchInput = document.querySelector('input[name="search"]');
        const statusSelect = document.querySelector('select[name="status"]');

        if (searchInput) {
            searchInput.addEventListener('input', triggerSearch);
        }
        if (statusSelect) {
            statusSelect.addEventListener('change', triggerSearch);
        }
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
            <h1>Reservations</h1>
        </div>
        <?php if ($error): ?>
            <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;margin-bottom:12px;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div style="padding:12px;background:#e7f7ed;border:1px solid #b8e0c2;color:#124b26;border-radius:4px;margin-bottom:12px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div style="margin-top:20px;" class="card">
            <h3>Search Reservations</h3>
            <form method="get" id="searchForm">
                <input type="hidden" name="page" value="reservations">
                <input type="text" name="search" placeholder="Search reservations" value="<?php echo htmlspecialchars($searchTerm); ?>" autocomplete="off">
                <button type="submit">Search</button>
                <button type="submit" name="clear">Clear</button>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Confirmed" <?php echo $statusFilter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="Checked-In" <?php echo $statusFilter === 'Checked-In' ? 'selected' : ''; ?>>Checked-In</option>
                    <option value="Checked-Out" <?php echo $statusFilter === 'Checked-Out' ? 'selected' : ''; ?>>Checked-Out</option>
                    <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </form>
        </div>

        <div style="margin-top:20px;" class="card">
            <h3>All Reservations</h3>
            <table id="reservationTable">
                <thead>
                    <tr><th>ID</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="reservationTableBody">
                <?php if (empty($recentReservations)): ?>
                    <tr><td colspan="7" class="muted">No recent reservations.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentReservations as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['reservation_id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($r['guest_name'] ?? '—'); ?>
                                <br><small style="color:#666;"><?php echo htmlspecialchars($r['contact_email'] ?? ''); ?></small>
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
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
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
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                                        <button class="delete-btn" type="submit">
                                            <img src="/admin/static/img/adminpanel_icons/delete.svg" alt="">
                                        </button>
                                    </form>
                                </div> 
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
