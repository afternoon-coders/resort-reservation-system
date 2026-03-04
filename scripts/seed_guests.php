<?php
// Seed guests into Guests table
require_once __DIR__ . '/../helpers/DB.php';

$guests = [
    ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@example.com', 'phone_number' => '09171234567', 'address' => 'Manila, Philippines'],
    ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@example.com', 'phone_number' => '09179876543', 'address' => 'Quezon City, Philippines'],
    ['first_name' => 'System', 'last_name' => 'Admin', 'email' => 'admin@example.com', 'phone_number' => '000', 'address' => 'Main Office'],
    ['first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@example.com', 'phone_number' => '111', 'address' => 'Guest House'],
    ['first_name' => 'Robert', 'last_name' => 'Johnson', 'email' => 'bob@example.com', 'phone_number' => '222', 'address' => 'Guest House'],
    ['first_name' => 'Hotel', 'last_name' => 'Staff', 'email' => 'staff@example.com', 'phone_number' => '333', 'address' => 'Staff Room'],
];

try {
    $pdo = DB::getPDO();
    $added = 0;

    foreach ($guests as $g) {
        $s = $pdo->prepare('SELECT guest_id FROM Guests WHERE email = :e LIMIT 1');
        $s->execute([':e' => $g['email']]);
        if ($s->fetch()) {
            echo "Skipping existing guest: {$g['email']}\n";
            continue;
        }

        $ins = $pdo->prepare('INSERT INTO Guests (first_name, last_name, email, phone_number, address) VALUES (:fn, :ln, :email, :phone, :addr)');
        $ins->execute([
            ':fn' => $g['first_name'],
            ':ln' => $g['last_name'],
            ':email' => $g['email'],
            ':phone' => $g['phone_number'],
            ':addr' => $g['address'],
        ]);

        echo "Added guest: {$g['first_name']} {$g['last_name']} ({$g['email']})\n";
        $added++;
    }

    echo "Done. Added {$added} guest(s).\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
