<?php
// Seed payments for reservations
require_once __DIR__ . '/../helpers/DB.php';

try {
    $pdo = DB::getPDO();

    $rq = $pdo->query("SELECT reservation_id, total_amount FROM Reservations LIMIT 20");
    $reservations = $rq->fetchAll();

    if (empty($reservations)) {
        echo "No reservations found to create payments for.\n";
        exit(0);
    }

    $added = 0;
    foreach ($reservations as $r) {
        // skip if payment already exists
        $sq = $pdo->prepare('SELECT payment_id FROM Payments WHERE reservation_id = :rid LIMIT 1');
        $sq->execute([':rid' => $r['reservation_id']]);
        if ($sq->fetch()) continue;

        $amt = ($r['total_amount'] ?? 1000) / 2.0; // make a partial payment
        $ins = $pdo->prepare('INSERT INTO Payments (reservation_id, amount_paid, payment_method, payment_status) VALUES (:rid, :amt, :method, :status)');
        $ins->execute([':rid' => $r['reservation_id'], ':amt' => $amt, ':method' => 'Credit Card', ':status' => 'Completed']);

        echo "Inserted payment for reservation {$r['reservation_id']} amount {$amt}\n";
        $added++;
    }

    echo "Done. Added {$added} payment(s).\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
