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
    <style>
        .rp-stack {
            display: grid;
            gap: 20px;
        }

        .rp-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .rp-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .rp-subtle {
            color: #666;
            font-size: 0.9rem;
        }

        .rp-good {
            color: #157347;
            font-weight: 600;
        }

        .rp-bad {
            color: #b02a37;
            font-weight: 600;
        }

        .rp-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }

        .rp-filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 160px;
        }

        .rp-filter-group label {
            font-size: 0.82rem;
            color: #4a5568;
            font-weight: 600;
        }

        .rp-filter-group input {
            border: 1px solid #d8dee8;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.9rem;
            background: #fff;
        }

        .rp-filter-note {
            margin-top: 10px;
        }

        .rp-btn-primary,
        .rp-btn-secondary,
        .rp-btn-export {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid transparent;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 12px;
            line-height: 1;
        }

        .rp-btn-primary {
            background: #1a73e8;
            color: #fff;
            border-color: #1a73e8;
        }

        .rp-btn-primary:hover {
            background: #1564c9;
            border-color: #1564c9;
        }

        .rp-btn-secondary {
            background: #fff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .rp-btn-secondary:hover {
            background: #f8fafc;
        }

        .rp-btn-export {
            background: #ecf5ff;
            color: #1059b6;
            border-color: #bfdbfe;
            white-space: nowrap;
        }

        .rp-btn-export:hover {
            background: #dbeafe;
        }

        .rp-chart-wrap {
            margin-top: 14px;
            height: 320px;
        }

        .rp-table-wrap {
            overflow-x: auto;
        }

        .rp-table-wrap table {
            min-width: 720px;
        }

        .rp-rank {
            display: inline-flex;
            width: 24px;
            height: 24px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #e7f0ff;
            color: #1a5ec9;
            font-weight: 700;
            font-size: 0.78rem;
        }

        .rp-meter {
            width: 100%;
            height: 7px;
            background: #edf1f6;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .rp-meter-fill {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #1a73e8, #47a0ff);
        }

        .rp-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .rp-pill.badge-neutral {
            background: #ececec;
            color: #444;
        }

        .rp-pill.badge-pending {
            background: #fce8d5;
            color: #7d4514;
        }

        .rp-pill.badge-confirmed {
            background: #dff2e8;
            color: #146741;
        }

        .rp-pill.badge-checkin {
            background: #d8ecff;
            color: #0d4f89;
        }

        .rp-pill.badge-checkout {
            background: #e3e7ec;
            color: #334155;
        }

        .rp-pill.badge-cancelled {
            background: #fce2e3;
            color: #8f1d25;
        }

        .rp-pill.badge-available {
            background: #daf4df;
            color: #115d2b;
        }

        .rp-pill.badge-occupied {
            background: #ffe4ce;
            color: #8b3f03;
        }

        .rp-pill.badge-maintenance {
            background: #fff6cf;
            color: #7a5b00;
        }

        .rp-pill.badge-outoforder {
            background: #ece5ff;
            color: #4e3694;
        }

        @media (max-width: 1200px) {
            .rp-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <h2>&#8369; <?php echo number_format($paymentsTotal, 2); ?></h2>
                <div class="muted">Total Completed Payments</div>
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

            <div class="card">
                <div class="rp-card-head">
                    <h3>Top Performing Rooms</h3>
                    <a class="rp-btn-export" href="<?php echo htmlspecialchars($exportRoomsUrl); ?>">Export CSV</a>
                </div>

                <div class="rp-table-wrap">
                    <?php if (empty($topRooms)): ?>
                        <div class="muted">No room performance data available for this range.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Cottage</th>
                                    <th>Type</th>
                                    <th>Bookings</th>
                                    <th>Guests</th>
                                    <th>Booked Gross</th>
                                    <th>Status</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topRooms as $index => $room): ?>
                                    <?php
                                        $roomStatusRaw = strtolower(str_replace(['_', ' '], ['', ''], (string)($room['status'] ?? '')));
                                        $roomStatusClass = 'badge-neutral';
                                        if ($roomStatusRaw === 'available') {
                                            $roomStatusClass = 'badge-available';
                                        } elseif ($roomStatusRaw === 'occupied') {
                                            $roomStatusClass = 'badge-occupied';
                                        } elseif ($roomStatusRaw === 'maintenance') {
                                            $roomStatusClass = 'badge-maintenance';
                                        } elseif ($roomStatusRaw === 'outoforder') {
                                            $roomStatusClass = 'badge-outoforder';
                                        }

                                        $bookingsCount = (int)($room['bookings_count'] ?? 0);
                                        $topBookingsMax = max(1, $topBookingsMax ?? 0);
                                        $performancePercent = ($bookingsCount / $topBookingsMax) * 100;
                                    ?>
                                    <tr>
                                        <td><span class="rp-rank"><?php echo $index + 1; ?></span></td>
                                        <td><?php echo htmlspecialchars((string)($room['cottage_number'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($room['type_name'] ?? 'N/A')); ?></td>
                                        <td><?php echo $bookingsCount; ?></td>
                                        <td><?php echo (int)($room['guests_count'] ?? 0); ?></td>
                                        <td>&#8369; <?php echo number_format((float)($room['gross_revenue'] ?? 0), 2); ?></td>
                                        <td><span class="rp-pill <?php echo $roomStatusClass; ?>"><?php echo htmlspecialchars((string)($room['status'] ?? 'Unknown')); ?></span></td>
                                        <td>
                                            <div class="rp-meter"><span class="rp-meter-fill" style="width: <?php echo number_format($performancePercent, 2, '.', ''); ?>%;"></span></div>
                                            <small class="rp-subtle"><?php echo number_format($performancePercent, 0); ?>% of top booking volume</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="rp-grid-2">
            <div class="card">
                <div class="rp-card-head">
                    <h3>Recent Reservations (Filtered)</h3>
                    <a class="rp-btn-export" href="<?php echo htmlspecialchars($exportReservationsUrl); ?>">Export CSV</a>
                </div>

                <div class="rp-table-wrap">
                    <?php if (empty($recentReservations)): ?>
                        <div class="muted">No reservations found for this range.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Booking Date</th>
                                    <th>Guest</th>
                                    <th>Cottage</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReservations as $reservation): ?>
                                    <?php
                                        $statusRaw = strtolower(str_replace('_', '-', (string)($reservation['status'] ?? '')));
                                        $statusClass = 'badge-neutral';
                                        if ($statusRaw === 'pending') {
                                            $statusClass = 'badge-pending';
                                        } elseif ($statusRaw === 'confirmed') {
                                            $statusClass = 'badge-confirmed';
                                        } elseif ($statusRaw === 'checked-in') {
                                            $statusClass = 'badge-checkin';
                                        } elseif ($statusRaw === 'checked-out') {
                                            $statusClass = 'badge-checkout';
                                        } elseif ($statusRaw === 'cancelled') {
                                            $statusClass = 'badge-cancelled';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo (int)($reservation['reservation_id'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars((string)($reservation['booking_date'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars(trim((string)($reservation['guest_name'] ?? '')) ?: 'Unknown Guest'); ?></td>
                                        <td><?php echo htmlspecialchars((string)($reservation['cottage_number'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($reservation['check_in_date'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($reservation['check_out_date'] ?? '')); ?></td>
                                        <td><span class="rp-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars((string)($reservation['status'] ?? 'Unknown')); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="rp-card-head">
                    <h3>Recent Users (Filtered)</h3>
                    <a class="rp-btn-export" href="<?php echo htmlspecialchars($exportUsersUrl); ?>">Export CSV</a>
                </div>

                <div class="rp-table-wrap">
                    <?php if (empty($recentUsers)): ?>
                        <div class="muted">No users found for this range.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <?php
                                        $fullName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
                                        $displayName = $fullName !== '' ? $fullName : (string)($user['username'] ?? 'Unknown');
                                        $roleClass = strtolower((string)($user['role'] ?? '')) === 'admin' ? 'badge-checkin' : 'badge-confirmed';
                                    ?>
                                    <tr>
                                        <td><?php echo (int)($user['user_id'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($displayName); ?></td>
                                        <td><?php echo htmlspecialchars((string)($user['username'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($user['account_email'] ?? '')); ?></td>
                                        <td><span class="rp-pill <?php echo $roleClass; ?>"><?php echo htmlspecialchars((string)($user['role'] ?? 'guest')); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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