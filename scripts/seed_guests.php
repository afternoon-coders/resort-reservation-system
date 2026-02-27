<?php
// Seed guests into Guests table
require_once __DIR__ . '/../helpers/DB.php';

$guests = [
    ['user_id' => null, 'first_name' => 'John', 'last_name' => 'Doe', 'contact_email' => 'john.doe@example.com', 'phone_number' => '09171234567'],
    ['user_id' => null, 'first_name' => 'Jane', 'last_name' => 'Smith', 'contact_email' => 'jane.smith@example.com', 'phone_number' => '09179876543'],
];

try {
    $pdo = DB::getPDO();
    $added = 0;

    foreach ($guests as $g) {
        $s = $pdo->prepare('SELECT guest_id FROM Guests WHERE contact_email = :e LIMIT 1');
        $s->execute([':e' => $g['contact_email']]);
        if ($s->fetch()) {
            echo "Skipping existing guest: {$g['contact_email']}\n";
            continue;
        }

        $ins = $pdo->prepare('INSERT INTO Guests (user_id, first_name, last_name, contact_email, phone_number) VALUES (:uid, :fn, :ln, :email, :phone)');
        $ins->execute([
            ':uid' => $g['user_id'],
            ':fn' => $g['first_name'],
            ':ln' => $g['last_name'],
            ':email' => $g['contact_email'],
            ':phone' => $g['phone_number'],
        ]);

        echo "Added guest: {$g['first_name']} {$g['last_name']} ({$g['contact_email']})\n";
        $added++;
    }

    echo "Done. Added {$added} guest(s).\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
