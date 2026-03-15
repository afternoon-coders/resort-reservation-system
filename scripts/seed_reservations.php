<?php
// Seed reservations for some guests and cottages
require_once __DIR__ . '/../helpers/DB.php';

try {
    $pdo = DB::getPDO();

    // Collect some guest ids and cottage ids
    $gq = $pdo->query('SELECT guest_id FROM Guests LIMIT 10');
    $guestIds = array_column($gq->fetchAll(), 'guest_id');

    $cq = $pdo->query("SELECT cottage_id, base_price FROM Cottages WHERE status = 'Available' LIMIT 10");
    $cottages = $cq->fetchAll();

    if (empty($guestIds) || empty($cottages)) {
        echo "Not enough guests or cottages to create reservations.\n";
        exit(0);
    }

    $added = 0;
    $today = new DateTimeImmutable();

    foreach ($guestIds as $i => $gid) {
        $c = $cottages[$i % count($cottages)];
        $checkInDate = $today->modify('+' . ($i + 1) . ' days')->setTime(15, 0, 0);
        $checkOutDate = $today->modify('+' . ($i + 3) . ' days')->setTime(11, 0, 0);
        $checkIn = $checkInDate->format('Y-m-d H:i:s');
        $checkOut = $checkOutDate->format('Y-m-d H:i:s');
        $checkInDay = $checkInDate->format('Y-m-d');
        $total = ($c['base_price'] ?? 1000) * 2; // two nights

        // skip if similar reservation exists (simplified check)
        $sq = $pdo->prepare('SELECT reservation_id FROM Reservations WHERE guest_id = :gid AND DATE(check_in_date) = :ci_day LIMIT 1');
        $sq->execute([':gid' => $gid, ':ci_day' => $checkInDay]);
        if ($sq->fetch()) continue;

        // Insert into Reservations
        $ins = $pdo->prepare('INSERT INTO Reservations (guest_id, check_in_date, check_out_date, total_amount, status) VALUES (:gid, :ci, :co, :total, :status)');
        $ins->execute([
            ':gid' => $gid,
            ':ci' => $checkIn,
            ':co' => $checkOut,
            ':total' => $total,
            ':status' => 'Pending',
        ]);
        $reservationId = $pdo->lastInsertId();

        // Insert into Reservation_Items
        $insItem = $pdo->prepare('INSERT INTO Reservation_Items (reservation_id, cottage_id, price_at_booking) VALUES (:rid, :cid, :price)');
        $insItem->execute([
            ':rid' => $reservationId,
            ':cid' => $c['cottage_id'],
            ':price' => $c['base_price'],
        ]);

        echo "Created reservation #{$reservationId} for guest {$gid} in cottage {$c['cottage_id']} ({$checkIn} to {$checkOut})\n";
        $added++;
    }

    echo "Done. Added {$added} reservation(s).\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
