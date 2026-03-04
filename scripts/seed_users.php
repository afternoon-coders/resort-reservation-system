<?php
// Seed users into Users table
require_once __DIR__ . '/../helpers/DB.php';

$users = [
    ['username' => 'admin', 'first_name' => 'System', 'middle_name' => null, 'last_name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'adminpass', 'role' => 'admin'],
    ['username' => 'alice', 'first_name' => 'Alice', 'middle_name' => 'Marie', 'last_name' => 'Smith', 'email' => 'alice@example.com', 'password' => 'password', 'role' => 'guest'],
    ['username' => 'bob', 'first_name' => 'Robert', 'middle_name' => 'J.', 'last_name' => 'Johnson', 'email' => 'bob@example.com', 'password' => 'password', 'role' => 'guest'],
    ['username' => 'staff', 'first_name' => 'Hotel', 'middle_name' => null, 'last_name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'staffpass', 'role' => 'staff'],
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

        $ins = $pdo->prepare('INSERT INTO Users (username, first_name, middle_name, last_name, password_hash, account_email, role) VALUES (:u, :fn, :mn, :ln, :p, :e, :r)');
        $ins->execute([
            ':u' => $u['username'],
            ':fn' => $u['first_name'],
            ':mn' => $u['middle_name'],
            ':ln' => $u['last_name'],
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
