<?php
require_once __DIR__ . '/../helpers/admin_backend.php';

function rp_parse_date($value): ?string
{
    $raw = trim((string)$value);
    if ($raw === '') {
        return null;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $raw);
    if (!$parsed || $parsed->format('Y-m-d') !== $raw) {
        return null;
    }

    return $raw;
}

function getDashboardStats(PDO $pdo): array {
    // Monthly Revenue
    $monthlyRevenue = (float)($pdo->query(
        "SELECT COALESCE(SUM(amount_paid), 0)
         FROM Payments
         WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
         AND YEAR(payment_date) = YEAR(CURRENT_DATE())"
    )->fetchColumn() ?: 0);

    // Occupancy Rate (Occupied cottages / Total cottages * 100)
    $totalCottages = (int)$pdo->query("SELECT COUNT(*) FROM Cottages")->fetchColumn();
    $occupiedCottages = (int)$pdo->query("SELECT COUNT(*) FROM Cottages WHERE status = 'Occupied'")->fetchColumn();
    $occupancyRate = $totalCottages > 0 ? round(($occupiedCottages / $totalCottages) * 100, 1) : 0;

    // Total Bookings (all time)
    $totalBooking = (int)$pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();

    // Average Stay (in nights)
    $averageStay = (float)($pdo->query(
        "SELECT COALESCE(AVG(DATEDIFF(check_out_date, check_in_date)), 0)
         FROM Reservations
         WHERE status NOT IN ('Cancelled')"
    )->fetchColumn() ?: 0);
    $averageStay = round($averageStay, 1);

    return [
        'monthlyRevenue' => number_format($monthlyRevenue, 2),
        'occupancyRate'  => $occupancyRate,
        'totalBooking'   => $totalBooking,
        'averageStay'    => $averageStay,
    ];
}

function rp_output_csv(string $filename, array $headers, array $rows): void
{
    if (!headers_sent()) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new RuntimeException('Unable to generate CSV output.');
    }

    fputcsv($output, $headers);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

$error = null;
$message = '';

$roomsTotal = 0;
$roomsAvailable = 0;
$reservationsTotal = 0;
$reservationsPending = 0;
$usersTotal = 0;
$paymentsTotal = 0.0;

$selectedRangeRevenue = 0.0;
$previousRangeRevenue = 0.0;
$rangeRevenueDelta = 0.0;
$selectedRangeBookings = 0;

$averageTrendRevenue = 0.0;
$totalTrendBookings = 0;

$monthlyTrendRows = [];
$topRooms = [];
$recentReservations = [];
$recentUsers = [];

$trendLabels = [];
$trendRevenueData = [];
$trendBookingsData = [];

$chartLabelsJson = '[]';
$chartRevenueJson = '[]';
$chartBookingsJson = '[]';

$defaultFromDate = (new DateTime('first day of -5 months'))->format('Y-m-01');
$defaultToDate = (new DateTime('today'))->format('Y-m-d');

$fromDate = rp_parse_date($_GET['from_date'] ?? null) ?? $defaultFromDate;
$toDate = rp_parse_date($_GET['to_date'] ?? null) ?? $defaultToDate;

if ($fromDate > $toDate) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
}

$rangeStartObj = new DateTime($fromDate);
$rangeEndObj = new DateTime($toDate);
$selectedRangeLabel = $rangeStartObj->format('M d, Y') . ' to ' . $rangeEndObj->format('M d, Y');

$queryBase = [
    'page' => 'reports',
    'from_date' => $fromDate,
    'to_date' => $toDate,
];

$resetUrl = 'index.php?page=reports';
$exportRoomsUrl = 'index.php?' . http_build_query(array_merge($queryBase, ['export' => 'rooms']));
$exportReservationsUrl = 'index.php?' . http_build_query(array_merge($queryBase, ['export' => 'reservations']));
$exportUsersUrl = 'index.php?' . http_build_query(array_merge($queryBase, ['export' => 'users']));

$exportType = strtolower(trim((string)($_GET['export'] ?? '')));

try {
    $pdo = admin_bootstrap();

    $flash = admin_pop_flash();
    if ($flash !== null) {
        if (($flash['type'] ?? '') === 'error') {
            $error = (string)$flash['message'];
        } else {
            $message = (string)$flash['message'];
        }
    }

    
    $stats = getDashboardStats($pdo);
    $monthlyRevenue = $stats['monthlyRevenue'];
    $occupancyRate  = $stats['occupancyRate'];
    $totalBooking   = $stats['totalBooking'];
    $averageStay    = $stats['averageStay'];

    // Summary stats (all-time)
    $roomsTotal = (int)$pdo->query("SELECT COUNT(*) FROM Cottages")->fetchColumn();
    $roomsAvailable = (int)$pdo->query("SELECT COUNT(*) FROM Cottages WHERE status = 'Available'")->fetchColumn();
    $reservationsTotal = (int)$pdo->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
    $reservationsPending = (int)$pdo->query("SELECT COUNT(*) FROM Reservations WHERE status = 'Pending'")->fetchColumn();
    $usersTotal = (int)$pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
    $paymentsTotal = (float)($pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM Payments WHERE payment_status = 'Completed'")->fetchColumn() ?: 0.0);

    // Selected range stats
    $selectedRevenueStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount_paid), 0)
         FROM Payments
         WHERE payment_status = 'Completed'
           AND payment_date >= :from_date
           AND payment_date < DATE_ADD(:to_date, INTERVAL 1 DAY)"
    );
    $selectedRevenueStmt->execute([
        ':from_date' => $fromDate,
        ':to_date' => $toDate,
    ]);
    $selectedRangeRevenue = (float)($selectedRevenueStmt->fetchColumn() ?: 0.0);

    $selectedBookingsStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM Reservations
         WHERE booking_date >= :from_date
           AND booking_date < DATE_ADD(:to_date, INTERVAL 1 DAY)"
    );
    $selectedBookingsStmt->execute([
        ':from_date' => $fromDate,
        ':to_date' => $toDate,
    ]);
    $selectedRangeBookings = (int)$selectedBookingsStmt->fetchColumn();

    $rangeDays = (int)$rangeStartObj->diff($rangeEndObj)->format('%a') + 1;
    $previousPeriodEndObj = (clone $rangeStartObj)->modify('-1 day');
    $previousPeriodStartObj = (clone $previousPeriodEndObj)->modify('-' . ($rangeDays - 1) . ' days');

    $previousFromDate = $previousPeriodStartObj->format('Y-m-d');
    $previousToDate = $previousPeriodEndObj->format('Y-m-d');

    $previousRevenueStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount_paid), 0)
         FROM Payments
         WHERE payment_status = 'Completed'
           AND payment_date >= :from_date
           AND payment_date < DATE_ADD(:to_date, INTERVAL 1 DAY)"
    );
    $previousRevenueStmt->execute([
        ':from_date' => $previousFromDate,
        ':to_date' => $previousToDate,
    ]);
    $previousRangeRevenue = (float)($previousRevenueStmt->fetchColumn() ?: 0.0);

    if ($previousRangeRevenue > 0.0) {
        $rangeRevenueDelta = (($selectedRangeRevenue - $previousRangeRevenue) / $previousRangeRevenue) * 100;
    } elseif ($selectedRangeRevenue > 0.0) {
        $rangeRevenueDelta = 100.0;
    } else {
        $rangeRevenueDelta = 0.0;
    }

    // Trend baseline: one point per month in selected range
    $trendStart = (clone $rangeStartObj)->modify('first day of this month');
    $trendEnd = (clone $rangeEndObj)->modify('first day of this month');

    $monthlyTrend = [];
    $monthCursor = clone $trendStart;
    while ($monthCursor <= $trendEnd) {
        $monthKey = $monthCursor->format('Y-m');
        $monthlyTrend[$monthKey] = [
            'label' => $monthCursor->format('M Y'),
            'revenue' => 0.0,
            'bookings' => 0,
        ];
        $monthCursor->modify('+1 month');
    }

    $trendRevenueStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym,
                COALESCE(SUM(CASE WHEN payment_status = 'Completed' THEN amount_paid ELSE 0 END), 0) AS revenue
         FROM Payments
         WHERE payment_date >= :from_date
           AND payment_date < DATE_ADD(:to_date, INTERVAL 1 DAY)
         GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
         ORDER BY ym ASC"
    );
    $trendRevenueStmt->execute([
        ':from_date' => $fromDate,
        ':to_date' => $toDate,
    ]);
    $trendRevenueRows = $trendRevenueStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trendRevenueRows as $row) {
        $key = (string)($row['ym'] ?? '');
        if ($key !== '' && isset($monthlyTrend[$key])) {
            $monthlyTrend[$key]['revenue'] = (float)($row['revenue'] ?? 0);
        }
    }

    $trendBookingStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(booking_date, '%Y-%m') AS ym,
                COUNT(*) AS bookings
         FROM Reservations
         WHERE booking_date >= :from_date
           AND booking_date < DATE_ADD(:to_date, INTERVAL 1 DAY)
         GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
         ORDER BY ym ASC"
    );
    $trendBookingStmt->execute([
        ':from_date' => $fromDate,
        ':to_date' => $toDate,
    ]);
    $trendBookingRows = $trendBookingStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trendBookingRows as $row) {
        $key = (string)($row['ym'] ?? '');
        if ($key !== '' && isset($monthlyTrend[$key])) {
            $monthlyTrend[$key]['bookings'] = (int)($row['bookings'] ?? 0);
        }
    }

    $monthlyTrendRows = array_values($monthlyTrend);
    $totalTrendRevenue = 0.0;
    $totalTrendBookings = 0;

    foreach ($monthlyTrendRows as $point) {
        $trendLabels[] = (string)$point['label'];
        $trendRevenueData[] = (float)$point['revenue'];
        $trendBookingsData[] = (int)$point['bookings'];

        $totalTrendRevenue += (float)$point['revenue'];
        $totalTrendBookings += (int)$point['bookings'];
    }

    $averageTrendRevenue = count($monthlyTrendRows) > 0 ? ($totalTrendRevenue / count($monthlyTrendRows)) : 0.0;

    // Top-performing cottages in selected range
    $topRoomsStmt = $pdo->prepare(
        "SELECT c.cottage_id,
                c.cottage_number,
                c.status,
                ct.type_name,
                COUNT(DISTINCT CASE
                    WHEN r.booking_date >= ? AND r.booking_date < DATE_ADD(?, INTERVAL 1 DAY)
                    THEN ri.reservation_id END
                ) AS bookings_count,
                COUNT(DISTINCT CASE
                    WHEN r.booking_date >= ? AND r.booking_date < DATE_ADD(?, INTERVAL 1 DAY)
                    THEN r.guest_id END
                ) AS guests_count,
                COALESCE(SUM(CASE
                    WHEN r.booking_date >= ? AND r.booking_date < DATE_ADD(?, INTERVAL 1 DAY)
                    THEN ri.price_at_booking ELSE 0 END
                ), 0) AS gross_revenue
         FROM Cottages c
         LEFT JOIN Cottage_Types ct ON c.type_id = ct.type_id
         LEFT JOIN Reservation_Items ri ON c.cottage_id = ri.cottage_id
         LEFT JOIN Reservations r ON ri.reservation_id = r.reservation_id
         GROUP BY c.cottage_id, c.cottage_number, c.status, ct.type_name
         ORDER BY bookings_count DESC, gross_revenue DESC, c.cottage_number ASC
         LIMIT 5"
    );
    $topRoomsStmt->execute([$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate]);
    $topRooms = $topRoomsStmt->fetchAll(PDO::FETCH_ASSOC);

    $topBookingsMax = 0;
    foreach ($topRooms as $room) {
        $bookings = (int)($room['bookings_count'] ?? 0);
        if ($bookings > $topBookingsMax) {
            $topBookingsMax = $bookings;
        }
    }

    // Recent reservations in selected range
    $recentReservationsStmt = $pdo->prepare(
        "SELECT r.reservation_id,
                DATE(r.booking_date) AS booking_date,
                r.check_in_date,
                r.check_out_date,
                r.status,
                CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, '')) AS guest_name,
                GROUP_CONCAT(c.cottage_number ORDER BY c.cottage_number SEPARATOR ', ') AS cottage_number
         FROM Reservations r
         LEFT JOIN Guests g ON r.guest_id = g.guest_id
         LEFT JOIN Reservation_Items ri ON r.reservation_id = ri.reservation_id
         LEFT JOIN Cottages c ON ri.cottage_id = c.cottage_id
         WHERE r.booking_date >= :from_date
           AND r.booking_date < DATE_ADD(:to_date, INTERVAL 1 DAY)
         GROUP BY r.reservation_id, r.booking_date, r.check_in_date, r.check_out_date, r.status, g.first_name, g.last_name
         ORDER BY r.reservation_id DESC
         LIMIT 12"
    );
    $recentReservationsStmt->execute([
        ':from_date' => $fromDate,
        ':to_date' => $toDate,
    ]);
    $recentReservations = $recentReservationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent users based on guest creation or last login in selected range
    $recentUsersStmt = $pdo->prepare(
        "SELECT u.user_id,
                u.username,
                u.account_email,
                u.role,
                COALESCE(g.first_name, '') AS first_name,
                COALESCE(g.last_name, '') AS last_name,
                g.created_at AS guest_created_at,
                u.last_login
         FROM Users u
         LEFT JOIN Guests g ON u.guest_id = g.guest_id
         WHERE (
                (g.created_at IS NOT NULL AND g.created_at >= :fd1 AND g.created_at < DATE_ADD(:td1, INTERVAL 1 DAY))
             OR (u.last_login IS NOT NULL AND u.last_login >= :fd2 AND u.last_login < DATE_ADD(:td2, INTERVAL 1 DAY))
         )
         ORDER BY u.user_id DESC
         LIMIT 12"
    );
    $recentUsersStmt->execute([
        ':fd1' => $fromDate,
        ':td1' => $toDate,
        ':fd2' => $fromDate,
        ':td2' => $toDate,
    ]);
    $recentUsers = $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($exportType !== '') {
        if ($exportType === 'rooms') {
            $csvRoomsStmt = $pdo->prepare(
                "SELECT c.cottage_number,
                        ct.type_name,
                        c.status,
                        COUNT(DISTINCT CASE
                            WHEN r.booking_date >= ? AND r.booking_date < DATE_ADD(?, INTERVAL 1 DAY)
                            THEN ri.reservation_id END
                        ) AS bookings_count,
                        COUNT(DISTINCT CASE
                            WHEN r.booking_date >= ? AND r.booking_date < DATE_ADD(?, INTERVAL 1 DAY)
                            THEN r.guest_id END
                        ) AS guests_count,
                        COALESCE(SUM(CASE
                            WHEN r.booking_date >= ? AND r.booking_date < DATE_ADD(?, INTERVAL 1 DAY)
                            THEN ri.price_at_booking ELSE 0 END
                        ), 0) AS gross_revenue
                 FROM Cottages c
                 LEFT JOIN Cottage_Types ct ON c.type_id = ct.type_id
                 LEFT JOIN Reservation_Items ri ON c.cottage_id = ri.cottage_id
                 LEFT JOIN Reservations r ON ri.reservation_id = r.reservation_id
                 GROUP BY c.cottage_id, c.cottage_number, c.status, ct.type_name
                 ORDER BY bookings_count DESC, gross_revenue DESC, c.cottage_number ASC"
            );
            $csvRoomsStmt->execute([$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate]);
            $csvRooms = $csvRoomsStmt->fetchAll(PDO::FETCH_ASSOC);

            $csvRows = [];
            foreach ($csvRooms as $idx => $room) {
                $csvRows[] = [
                    $idx + 1,
                    (string)($room['cottage_number'] ?? ''),
                    (string)($room['type_name'] ?? ''),
                    (string)($room['status'] ?? ''),
                    (int)($room['bookings_count'] ?? 0),
                    (int)($room['guests_count'] ?? 0),
                    number_format((float)($room['gross_revenue'] ?? 0), 2, '.', ''),
                ];
            }

            rp_output_csv(
                'rooms_report_' . $fromDate . '_to_' . $toDate . '.csv',
                ['Rank', 'Cottage', 'Type', 'Status', 'Bookings', 'Guests', 'Booked Gross'],
                $csvRows
            );
        }

        if ($exportType === 'reservations') {
            $csvReservationsStmt = $pdo->prepare(
                "SELECT r.reservation_id,
                        DATE(r.booking_date) AS booking_date,
                        r.check_in_date,
                        r.check_out_date,
                        r.status,
                        r.total_amount,
                        CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, '')) AS guest_name,
                        GROUP_CONCAT(c.cottage_number ORDER BY c.cottage_number SEPARATOR ', ') AS cottage_number
                 FROM Reservations r
                 LEFT JOIN Guests g ON r.guest_id = g.guest_id
                 LEFT JOIN Reservation_Items ri ON r.reservation_id = ri.reservation_id
                 LEFT JOIN Cottages c ON ri.cottage_id = c.cottage_id
                 WHERE r.booking_date >= :from_date
                   AND r.booking_date < DATE_ADD(:to_date, INTERVAL 1 DAY)
                 GROUP BY r.reservation_id, r.booking_date, r.check_in_date, r.check_out_date, r.status, r.total_amount, g.first_name, g.last_name
                 ORDER BY r.reservation_id DESC"
            );
            $csvReservationsStmt->execute([
                ':from_date' => $fromDate,
                ':to_date' => $toDate,
            ]);
            $csvReservations = $csvReservationsStmt->fetchAll(PDO::FETCH_ASSOC);

            $csvRows = [];
            foreach ($csvReservations as $reservation) {
                $csvRows[] = [
                    (int)($reservation['reservation_id'] ?? 0),
                    (string)($reservation['booking_date'] ?? ''),
                    trim((string)($reservation['guest_name'] ?? '')),
                    (string)($reservation['cottage_number'] ?? ''),
                    (string)($reservation['check_in_date'] ?? ''),
                    (string)($reservation['check_out_date'] ?? ''),
                    (string)($reservation['status'] ?? ''),
                    number_format((float)($reservation['total_amount'] ?? 0), 2, '.', ''),
                ];
            }

            rp_output_csv(
                'reservations_report_' . $fromDate . '_to_' . $toDate . '.csv',
                ['Reservation ID', 'Booking Date', 'Guest', 'Cottages', 'Check-In', 'Check-Out', 'Status', 'Total Amount'],
                $csvRows
            );
        }

        if ($exportType === 'users') {
            $csvUsersStmt = $pdo->prepare(
                "SELECT u.user_id,
                        u.username,
                        u.account_email,
                        u.role,
                        COALESCE(g.first_name, '') AS first_name,
                        COALESCE(g.last_name, '') AS last_name,
                        g.created_at AS guest_created_at,
                        u.last_login
                 FROM Users u
                 LEFT JOIN Guests g ON u.guest_id = g.guest_id
                 WHERE (
                        (g.created_at IS NOT NULL AND g.created_at >= :fd1 AND g.created_at < DATE_ADD(:td1, INTERVAL 1 DAY))
                     OR (u.last_login IS NOT NULL AND u.last_login >= :fd2 AND u.last_login < DATE_ADD(:td2, INTERVAL 1 DAY))
                 )
                 ORDER BY u.user_id DESC"
            );
            $csvUsersStmt->execute([
                ':fd1' => $fromDate,
                ':td1' => $toDate,
                ':fd2' => $fromDate,
                ':td2' => $toDate,
            ]);
            $csvUsers = $csvUsersStmt->fetchAll(PDO::FETCH_ASSOC);

            $csvRows = [];
            foreach ($csvUsers as $user) {
                $displayName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
                if ($displayName === '') {
                    $displayName = (string)($user['username'] ?? '');
                }

                $csvRows[] = [
                    (int)($user['user_id'] ?? 0),
                    $displayName,
                    (string)($user['username'] ?? ''),
                    (string)($user['account_email'] ?? ''),
                    (string)($user['role'] ?? ''),
                    (string)($user['guest_created_at'] ?? ''),
                    (string)($user['last_login'] ?? ''),
                ];
            }

            rp_output_csv(
                'users_report_' . $fromDate . '_to_' . $toDate . '.csv',
                ['User ID', 'Name', 'Username', 'Email', 'Role', 'Guest Created At', 'Last Login'],
                $csvRows
            );
        }

        $message = 'Unsupported export type requested.';
    }

    $chartLabelsJson = json_encode($trendLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $chartRevenueJson = json_encode($trendRevenueData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $chartBookingsJson = json_encode($trendBookingsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    if ($chartLabelsJson === false) {
        $chartLabelsJson = '[]';
    }
    if ($chartRevenueJson === false) {
        $chartRevenueJson = '[]';
    }
    if ($chartBookingsJson === false) {
        $chartBookingsJson = '[]';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
    $error = $e->getMessage();
    $roomsTotal = 0;
    $monthlyReservations = 0;
    $monthlyGuests = 0;
    $currentMonthRevenue = 0.0;
    $monthlyRevenue = '0.00';   // ← add these
    $occupancyRate  = 0;
    $totalBooking   = 0;
    $averageStay    = 0;
    $recentReservations = [];
    $recentUsers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <div class="rp-stack">
        <div class="admin-header">
            <h1>Reports</h1>
            <p>Track business performance and recent admin activity.</p>
        </div>

        <?php if ($error): ?>
            <div style="padding:12px;background:#fdecea;border:1px solid #f5c2c2;color:#6b0b0b;border-radius:4px;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div style="padding:12px;background:#e7f7ed;border:1px solid #b8e0c2;color:#124b26;border-radius:4px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="rp-card-head">
                <h3>Date Range Filters</h3>
            </div>
            <form method="get" class="rp-filter-form">
                <input type="hidden" name="page" value="reports">

                <div class="rp-filter-group">
                    <label for="from_date">From</label>
                    <input id="from_date" type="date" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" required>
                </div>

                <div class="rp-filter-group">
                    <label for="to_date">To</label>
                    <input id="to_date" type="date" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" required>
                </div>

                <button class="rp-btn-primary" type="submit">Apply Filters</button>
                <a class="rp-btn-secondary" href="<?php echo htmlspecialchars($resetUrl); ?>">Reset</a>
            </form>
            <p class="rp-subtle rp-filter-note">Active range: <?php echo htmlspecialchars($selectedRangeLabel); ?></p>
        </div>

        <!-- TODO: addd Monthly Revenue, Occupancy Rate, Total Bookings, Avg. Stay -->
        <div class="grid" style="margin-top: 0px;">
            <div class="card-stat">
                <h2><?php echo $monthlyRevenue; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Monthly Revenue</div>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1a73e8"><path d="M200-120q-33 0-56.5-23.5T120-200v-640h80v640h640v80H200Zm40-120v-360h160v360H240Zm200 0v-560h160v560H440Zm200 0v-200h160v200H640Z"/></svg>
                </div>
            </div>
            <div class="card-stat">
                <h2><?php echo $occupancyRate; ?>%</h2>
                <div class="card-stat-content">
                    <div class="muted">Occupancy Rate</div>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5be288"><path d="M80-200v-240q0-27 11-49t29-39v-112q0-50 35-85t85-35h160q23 0 43 8.5t37 23.5q17-15 37-23.5t43-8.5h160q50 0 85 35t35 85v112q18 17 29 39t11 49v240h-80v-80H160v80H80Zm440-360h240v-80q0-17-11.5-28.5T720-680H560q-17 0-28.5 11.5T520-640v80Zm-320 0h240v-80q0-17-11.5-28.5T400-680H240q-17 0-28.5 11.5T200-640v80Zm-40 200h640v-80q0-17-11.5-28.5T760-480H200q-17 0-28.5 11.5T160-440v80Zm640 0H160h640Z"/></svg>
                </div>
            </div>
            <div class="card-stat">
                <h2><?php echo $totalBooking; ?></h2>
                <div class="card-stat-content">
                    <div class="muted">Total Bookings</div>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5be288"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm466 0q-47 47-113 47-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-8１T5４４−７９２ｑ１４−５　２８−６．５ｔ２８−１．５ｑ６６　０　１１３　４７ｔ４７　１１３ｑ０　６６−４７　１１３ＺＭ１２０−２４０ｈ４８０ｖ−３２ｑ０−１１−５．５−２０Ｔ５８０−３０６ｑ−５４−２７−１０９−４０．５Ｔ３６０−３６０ｑ−５６　０−１１１　１３．５Ｔ１４０−３０６ｑ−９　５−１４．５　１４ｔ−５．５　２０ｖ３２Ｚｍ２９６．５−３４３．５Ｑ４４０−６０７　４４０−６４０ｔ−２３．５−５６．５Ｑ３９３−７２０　３６０−７２０ｔ−５６．５　２３．５Ｑ２８０−６７３　２８₀−６４₀ｔ２₃．₅　₅₆．₅Ｑ₃₂₇−₅₆₀　₃₆₀−₅₆₀ｔ₅₆．₅−₂₃．₅ＺＭ³⁶⁰−₂⁴⁰Ｚｍ⁰−⁴⁰⁰Ｚ"/></svg>
                </div>
            </div>
            <div class="card-stat">
                <h2><?php echo $averageStay; ?> days</h2>
                <div class="card-stat-content">
                    <div class="muted">Average Stay</div>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1a73e8"><path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Z"/></svg>
                </div>
            </div>
        </div>

        <div class="rp-grid-2">
            <div class="card">
                <div class="rp-card-head">
                    <h3>Revenue and Booking Trend</h3>
                </div>
                <p class="rp-subtle">
                    Revenue in selected range:
                    <strong>&#8369; <?php echo number_format($selectedRangeRevenue, 2); ?></strong>
                    <?php if ($rangeRevenueDelta >= 0): ?>
                        <span class="rp-good">(+<?php echo number_format($rangeRevenueDelta, 1); ?>% vs previous period)</span>
                    <?php else: ?>
                        <span class="rp-bad"><?php echo number_format($rangeRevenueDelta, 1); ?>% vs previous period</span>
                    <?php endif; ?>
                </p>
                <p class="rp-subtle">Bookings in range: <?php echo $selectedRangeBookings; ?> | Avg monthly revenue in range: &#8369; <?php echo number_format($averageTrendRevenue, 2); ?> | Total trend bookings: <?php echo $totalTrendBookings; ?></p>

                <?php if (empty($monthlyTrendRows)): ?>
                    <div class="muted" style="margin-top:12px;">No trend data available for this range.</div>
                <?php else: ?>
                    <div class="rp-chart-wrap">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="padding-bottom: 50px;">
                <div class="rp-card-head">
                    <h3>Top Performing Rooms</h3>
                    <a class="rp-btn-export" href="<?php echo htmlspecialchars($exportRoomsUrl); ?>">Export CSV</a>
                </div>

                <div class="rp-table-wrap">
                    <?php if (empty($topRooms)): ?>
                        <div class="muted">No room performance data available for this range.</div>
                    <?php else: ?>
                        <?php foreach ($topRooms as $index => $room): ?>
                            <div class="top-perf-rooms">
                                <div class="top-perf-room row">
                                    <div style="display: flex; justify-content: left; align-items: center; width: 60px;"><span class="top-perf-rank"><?php echo $index + 1; ?></span></div>
                                    <div class="top-perf-info col">
                                        <div class="top-perf-type"><?php echo htmlspecialchars((string)($room['type_name'] ?? 'N/A')); ?></div>
                                        <div class="top-perf-cottage"><?php echo htmlspecialchars((string)($room['cottage_number'] ?? 'N/A')); ?></div>
                                        <div class="top-perf-bookings"><?php echo (int)($room['bookings_count'] ?? 0); ?> bookings</div>
                                    </div>
                                    <div class="top-perf-revenue row">₱ <?php echo number_format((float)($room['gross_revenue'] ?? 0), 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const canvas = document.getElementById('revenueTrendChart');
            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            const labels = <?php echo $chartLabelsJson; ?>;
            const revenueData = <?php echo $chartRevenueJson; ?>;
            const bookingsData = <?php echo $chartBookingsJson; ?>;

            if (!Array.isArray(labels) || labels.length === 0) {
                return;
            }

            new Chart(canvas, {
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Bookings',
                            data: bookingsData,
                            yAxisID: 'yBookings',
                            backgroundColor: 'rgba(32, 163, 107, 0.55)',
                            borderColor: 'rgba(32, 163, 107, 0.9)',
                            borderWidth: 1,
                            maxBarThickness: 42,
                            borderRadius: 6,
                        },
                        {
                            type: 'line',
                            label: 'Revenue (PHP)',
                            data: revenueData,
                            yAxisID: 'yRevenue',
                            borderColor: '#1a73e8',
                            backgroundColor: 'rgba(26, 115, 232, 0.18)',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 4,
                            tension: 0.32,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    },
                    scales: {
                        yBookings: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Bookings',
                            }
                        },
                        yRevenue: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: 'Revenue (PHP)',
                            },
                            ticks: {
                                callback: function (value) {
                                    return 'PHP ' + Number(value).toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        })();
    </script>
</body>
</html>