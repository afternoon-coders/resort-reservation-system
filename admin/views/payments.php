<?php
require_once __DIR__ . '/../helpers/admin_backend.php';

$error        = null;
$message      = '';
$csrfToken    = '';
$reservations = [];
$searchTerm   = '';
$dateFilter   = '';

try {
    $pdo       = admin_bootstrap();
    $csrfToken = admin_get_csrf_token();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_require_csrf_token($_POST['csrf_token'] ?? null);

        $action = trim((string)($_POST['action'] ?? ''));
        $result = admin_dispatch_action($pdo, $action, $_POST);
        admin_set_flash($result['ok'] ? 'success' : 'error', $result['message']);

        admin_redirect_to_page('payments');
    }

    $flash = admin_pop_flash();
    if ($flash !== null) {
        if ($flash['type'] === 'error') {
            $error = $flash['message'];
        } else {
            $message = $flash['message'];
        }
    }

    $searchTerm = trim((string)($_GET['search'] ?? ''));
    if (strlen($searchTerm) > 100) {
        $searchTerm = substr($searchTerm, 0, 100);
    }

    // Default to today; validate the date input
    $dateFilter = trim((string)($_GET['date'] ?? date('Y-m-d')));
    $dt = DateTime::createFromFormat('Y-m-d', $dateFilter);
    if (!$dt || $dt->format('Y-m-d') !== $dateFilter) {
        $dateFilter = date('Y-m-d');
    }

    // Query pending / confirmed reservations
    $query = "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.status, r.total_amount,
                     CONCAT(COALESCE(g.first_name,''), ' ', COALESCE(g.last_name,'')) AS guest_name,
                     g.email, g.phone_number,
                     GROUP_CONCAT(DISTINCT c.cottage_number ORDER BY c.cottage_number SEPARATOR ', ') AS cottage_numbers,
                     COALESCE(SUM(CASE WHEN p.payment_status = 'Completed' THEN p.amount_paid ELSE 0 END), 0) AS amount_paid_so_far
              FROM Reservations r
              JOIN Guests g ON r.guest_id = g.guest_id
              LEFT JOIN Reservation_Items ri ON r.reservation_id = ri.reservation_id
              LEFT JOIN Cottages c ON ri.cottage_id = c.cottage_id
              LEFT JOIN Payments p ON r.reservation_id = p.reservation_id
              WHERE r.status IN ('Pending', 'Confirmed')";

    $params = [];

    if ($searchTerm !== '') {
        $query .= " AND (g.first_name LIKE :s1 OR g.last_name LIKE :s2 OR g.email LIKE :s3";
        $params[':s1'] = "%{$searchTerm}%";
        $params[':s2'] = "%{$searchTerm}%";
        $params[':s3'] = "%{$searchTerm}%";
        if (is_numeric($searchTerm)) {
            $query .= " OR r.reservation_id = :res_id";
            $params[':res_id'] = (int)$searchTerm;
        }
        $query .= ")";
    } else {
        $query .= " AND r.check_in_date = :date";
        $params[':date'] = $dateFilter;
    }

    $query .= " GROUP BY r.reservation_id ORDER BY r.check_in_date ASC, r.reservation_id ASC LIMIT 50";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();

} catch (Throwable $e) {
    $error        = $e->getMessage();
    $reservations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/static/css/style.css">
<title>Payments</title>
<style>
/* ── Modal overlay ───────────────────────────────────────────── */
.pm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.pm-overlay.open { display: flex; }

.pm-modal {
    background: #fff;
    border-radius: 8px;
    padding: 28px;
    max-width: 520px;
    width: 92%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 32px rgba(0,0,0,.18);
}
.pm-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}
.pm-modal-header h2 { margin: 0; font-size: 1.2rem; }
.pm-modal-close {
    background: none;
    border: none;
    font-size: 1.6rem;
    cursor: pointer;
    color: #666;
    line-height: 1;
    padding: 0;
}
.pm-modal-close:hover { color: #111; }

/* ── Reservation summary inside modal ───────────────────────── */
.pm-summary {
    background: #f8f9fa;
    border: 1px solid #e3e6ea;
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 18px;
    font-size: 14px;
    line-height: 1.75;
}
.pm-summary hr { border: none; border-top: 1px solid #dee2e6; margin: 8px 0; }

/* ── Form elements inside modal ─────────────────────────────── */
.pm-form-group { margin-bottom: 14px; }
.pm-form-group label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 14px; }
.pm-form-group input,
.pm-form-group select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 14px;
}
.pm-form-group input:focus,
.pm-form-group select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37,99,235,.15);
}
.pm-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 6px; }
.pm-btn-cancel {
    padding: 8px 16px;
    border: 1px solid #ccc;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}
.pm-btn-cancel:hover { background: #f3f4f6; }
.pm-btn-pay {
    padding: 8px 20px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
}
.pm-btn-pay:hover { background: #1d4ed8; }

/* ── Table action button ─────────────────────────────────────── */
.take-payment-btn {
    padding: 5px 12px;
    background: #16a34a;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}
.take-payment-btn:hover { background: #15803d; }
.take-payment-btn:disabled,
.take-payment-btn[disabled] {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
    pointer-events: none;
}

/* ── Status badges ───────────────────────────────────────────── */
.badge-pending   { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 12px; font-size: 12px; white-space: nowrap; }
.badge-confirmed { background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 12px; font-size: 12px; white-space: nowrap; }
</style>
</head>
<body>

<!-- ── Payment Modal ───────────────────────────────────────────── -->
<div id="paymentModal" class="pm-overlay">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2>Take Payment &amp; Check-In</h2>
            <button class="pm-modal-close" onclick="closePaymentModal()" type="button">&times;</button>
        </div>

        <div id="modalSummary" class="pm-summary"></div>

        <form method="post" id="paymentForm">
            <input type="hidden" name="action"         value="process_payment">
            <input type="hidden" name="csrf_token"     value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="reservation_id" id="modalReservationId">

            <div class="pm-form-group">
                <label for="modalAmount">Amount Paid (&#8369;)</label>
                <input type="number" name="amount_paid" id="modalAmount"
                       step="0.01" min="0.01" required placeholder="0.00">
            </div>

            <div class="pm-form-group">
                <label for="modalMethod">Payment Method</label>
                <select name="payment_method" id="modalMethod" required>
                    <option value="Cash">Cash</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="PayPal">PayPal</option>
                </select>
            </div>

            <div class="pm-form-group">
                <label for="modalRef">
                    Transaction Reference
                    <span style="font-weight:normal;color:#888;">(optional)</span>
                </label>
                <input type="text" name="transaction_ref" id="modalRef"
                       maxlength="100" placeholder="Receipt #, bank ref, etc.">
            </div>

            <div class="pm-actions">
                <button type="button" class="pm-btn-cancel" onclick="closePaymentModal()">Cancel</button>
                <button type="submit" class="pm-btn-pay">Confirm Payment &amp; Check-In</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="admin-header">
    <h1>Payments</h1>
    <p>Process guest payments and check them in upon arrival</p>
</div>

<?php if ($error): ?>
    <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;margin-bottom:12px;">
        Error: <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
<?php if ($message): ?>
    <div style="padding:12px;background:#e7f7ed;border:1px solid #b8e0c2;color:#124b26;border-radius:4px;margin-bottom:12px;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- ── Filters ─────────────────────────────────────────────────── -->
<div style="margin-top:16px;" class="card">
    <form method="get" id="filterForm" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="payments">

        <div>
            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Check-in Date</label>
            <input type="date" name="date"
                   value="<?php echo htmlspecialchars($dateFilter); ?>"
                   style="padding:7px 10px;border:1px solid #ccc;border-radius:4px;"
                   onchange="this.form.submit()">
        </div>

        <div style="flex:1;min-width:220px;">
            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;">Search Guest / Reservation #</label>
            <input type="text" name="search"
                   value="<?php echo htmlspecialchars($searchTerm); ?>"
                   placeholder="Name, email, or reservation #"
                   style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;"
                   autocomplete="off">
        </div>

        <button type="submit"
                style="padding:7px 16px;background:#2563eb;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:600;">
            Search
        </button>
        <a href="?page=payments"
           style="padding:7px 14px;border:1px solid #ccc;background:#fff;border-radius:4px;text-decoration:none;color:#333;">
            Today
        </a>
    </form>
</div>

<!-- ── Reservations Table ──────────────────────────────────────── -->
<div style="margin-top:16px;" class="card">
    <h3 style="margin-top:0;">
        <?php if ($searchTerm !== ''): ?>
            Search results for &ldquo;<?php echo htmlspecialchars($searchTerm); ?>&rdquo;
        <?php else: ?>
            Arrivals on <?php echo htmlspecialchars(date('F j, Y', strtotime($dateFilter))); ?>
        <?php endif; ?>
        <span style="font-size:13px;font-weight:normal;color:#666;margin-left:8px;">
            (<?php echo count($reservations); ?> reservation<?php echo count($reservations) !== 1 ? 's' : ''; ?>)
        </span>
    </h3>

    <?php if (empty($reservations)): ?>
        <p class="muted">
            No pending or confirmed reservations found
            <?php echo $searchTerm === '' ? ' for this date.' : '.'; ?>
        </p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Guest</th>
                        <th>Cottage(s)</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $r):
                    $total       = (float)$r['total_amount'];
                    $paid        = (float)$r['amount_paid_so_far'];
                    $balance     = max(0.0, round($total - $paid, 2));
                    $isConfirmed = $r['status'] === 'Confirmed';
                    $statusClass = $isConfirmed ? 'badge-confirmed' : 'badge-pending';
                ?>
                    <tr>
                        <td><?php echo (int)$r['reservation_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($r['guest_name'] ?: '—'); ?></strong>
                            <?php if (!empty($r['email'])): ?>
                                <br><small style="color:#666;"><?php echo htmlspecialchars($r['email']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($r['phone_number'])): ?>
                                <br><small style="color:#666;"><?php echo htmlspecialchars($r['phone_number']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['cottage_numbers'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($r['check_in_date']); ?></td>
                        <td><?php echo htmlspecialchars($r['check_out_date']); ?></td>
                        <td>&#8369;<?php echo number_format($total, 2); ?></td>
                        <td><?php echo $paid > 0 ? '&#8369;' . number_format($paid, 2) : '—'; ?></td>
                        <td style="color:<?php echo $balance > 0 ? '#b91c1c' : '#16a34a'; ?>;font-weight:600;">
                            &#8369;<?php echo number_format($balance, 2); ?>
                        </td>
                        <td><span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                        <td>
                            <?php if ($isConfirmed): ?>
                                <button class="take-payment-btn"
                                        type="button"
                                        data-id="<?php echo (int)$r['reservation_id']; ?>"
                                        data-guest="<?php echo htmlspecialchars($r['guest_name'] ?: ''); ?>"
                                        data-email="<?php echo htmlspecialchars($r['email'] ?? ''); ?>"
                                        data-phone="<?php echo htmlspecialchars($r['phone_number'] ?? ''); ?>"
                                        data-checkin="<?php echo htmlspecialchars($r['check_in_date']); ?>"
                                        data-checkout="<?php echo htmlspecialchars($r['check_out_date']); ?>"
                                        data-cottages="<?php echo htmlspecialchars($r['cottage_numbers'] ?: '—'); ?>"
                                        data-total="<?php echo number_format($total, 2, '.', ''); ?>"
                                        data-paid="<?php echo number_format($paid, 2, '.', ''); ?>"
                                        onclick="openPaymentModal(this)">
                                    Take Payment
                                </button>
                            <?php else: ?>
                                <button class="take-payment-btn"
                                        type="button"
                                        disabled
                                        title="Reservation must be Confirmed before payment can be taken">
                                    Take Payment
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

function openPaymentModal(btn) {
    const id       = btn.dataset.id;
    const guest    = btn.dataset.guest;
    const email    = btn.dataset.email;
    const phone    = btn.dataset.phone;
    const checkin  = btn.dataset.checkin;
    const checkout = btn.dataset.checkout;
    const cottages = btn.dataset.cottages;
    const total    = parseFloat(btn.dataset.total);
    const paid     = parseFloat(btn.dataset.paid);
    const balance  = Math.max(0, +(total - paid).toFixed(2));

    document.getElementById('modalReservationId').value = id;
    // Pre-fill with balance due; fall back to full total if already paid
    document.getElementById('modalAmount').value = (balance > 0 ? balance : total).toFixed(2);
    document.getElementById('modalRef').value    = '';

    const balanceColor = balance > 0 ? '#b91c1c' : '#16a34a';
    const paidRow = paid > 0
        ? `<div>Already Paid: &#8369;${paid.toFixed(2)}</div>`
        : '';

    document.getElementById('modalSummary').innerHTML = `
        <div><strong>Reservation #${escHtml(id)}</strong></div>
        <div style="margin-top:6px;">Guest: <strong>${escHtml(guest || '—')}</strong></div>
        ${email ? `<div>Email: ${escHtml(email)}</div>` : ''}
        ${phone ? `<div>Phone: ${escHtml(phone)}</div>` : ''}
        <div style="margin-top:6px;">Cottage(s): ${escHtml(cottages)}</div>
        <div>Check-in: <strong>${escHtml(checkin)}</strong> &rarr; Check-out: <strong>${escHtml(checkout)}</strong></div>
        <hr>
        <div>Total Amount: <strong>&#8369;${total.toFixed(2)}</strong></div>
        ${paidRow}
        <div>Balance Due: <strong style="color:${balanceColor}">&#8369;${balance.toFixed(2)}</strong></div>
    `;

    document.getElementById('paymentModal').classList.add('open');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('open');
}

// Close on backdrop click
document.getElementById('paymentModal').addEventListener('click', function (e) {
    if (e.target === this) closePaymentModal();
});
</script>

</body>
</html>
