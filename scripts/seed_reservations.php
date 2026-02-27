<?php
// Seed reservations for some guests and cottages
require_once __DIR__ . '/../helpers/DB.php';

try {
    $pdo = DB::getPDO();

    // Collect some guest ids and cottage ids
    $gq = $pdo->query('SELECT guest_id FROM Guests LIMIT 10');
    $guestIds = array_column($gq->fetchAll(), 'guest_id');

    $cq = $pdo->query('SELECT cottage_id, base_price FROM Cottages WHERE is_available = 1 LIMIT 10');
    $cottages = $cq->fetchAll();

    if (empty($guestIds) || empty($cottages)) {
        echo "Not enough guests or cottages to create reservations.\n";
        exit(0);
    }

    $added = 0;
    $today = new DateTimeImmutable();

    foreach ($guestIds as $i => $gid) {
        $c = $cottages[$i % count($cottages)];
        $checkIn = $today->modify('+' . ($i + 1) . ' days')->format('Y-m-d');
        $checkOut = $today->modify('+' . ($i + 3) . ' days')->format('Y-m-d');
        $total = ($c['base_price'] ?? 1000) * 2; // two nights

        // skip if similar reservation exists
        $sq = $pdo->prepare('SELECT reservation_id FROM Reservations WHERE guest_id = :gid AND cottage_id = :cid AND check_in_date = :ci LIMIT 1');
        $sq->execute([':gid' => $gid, ':cid' => $c['cottage_id'], ':ci' => $checkIn]);
        if ($sq->fetch()) continue;

        $ins = $pdo->prepare('INSERT INTO Reservations (guest_id, cottage_id, check_in_date, check_out_date, total_amount, status) VALUES (:gid, :cid, :ci, :co, :total, :status)');
        $ins->execute([
            ':gid' => $gid,
            ':cid' => $c['cottage_id'],
            ':ci' => $checkIn,
            ':co' => $checkOut,
            ':total' => $total,
            ':status' => 'Pending',
        ]);

        echo "Created reservation for guest {$gid} in cottage {$c['cottage_id']} ({$checkIn} to {$checkOut})\n";
        $added++;
    }

    echo "Done. Added {$added} reservation(s).\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
