<?php
// Seed users into Users table
require_once __DIR__ . '/../helpers/DB.php';

$users = [
    ['username' => 'admin', 'first_name' => 'System', 'last_name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'adminpass', 'role' => 'admin'],
    ['guest_id' => 3,'username' => 'alice', 'first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@example.com', 'password' => 'password', 'role' => 'guest'],
    ['guest_id' => 4,'username' => 'bob', 'first_name' => 'Robert', 'last_name' => 'Johnson', 'email' => 'bob@example.com', 'password' => 'password', 'role' => 'guest'],
    ['username' => 'staff', 'first_name' => 'Hotel', 'last_name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'staffpass', 'role' => 'staff'],
];

try {
    $pdo = DB::getPDO();
    $added = 0;

    foreach ($users as $u) {
        $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE username = :u OR account_email = :e LIMIT 1');
        $stmt->execute([':u' => $u['username'], ':e' => $u['email']]);
        if ($stmt->fetch()) {
            echo "Skipping existing user: {$u['username']}\n";
            continue;
        }

        // Logic: guest_id is ONLY for the 'guest' role. 
        // Admin and Staff roles do not have a corresponding profile in the Guests table.
        $guestId = null;
        if ($u['role'] === 'guest') {
            $gstmt = $pdo->prepare('SELECT guest_id FROM Guests WHERE email = :e LIMIT 1');
            $gstmt->execute([':e' => $u['email']]);
            $guest = $gstmt->fetch();
            $guestId = $guest ? (int)$guest['guest_id'] : null;
        }

        $ins = $pdo->prepare('INSERT INTO Users (guest_id, username, password_hash, account_email, role) VALUES (:gid, :u, :p, :e, :r)');
        $ins->execute([
            ':gid' => $guestId,
            ':u' => $u['username'],
            ':p' => password_hash($u['password'], PASSWORD_BCRYPT),
            ':e' => $u['email'],
            ':r' => $u['role'],
        ]);

        echo "Added user: {$u['username']} ({$u['email']})\n";
        $added++;
    }

    echo "Done. Added {$added} user(s).\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
