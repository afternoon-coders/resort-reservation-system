<?php
// Seed users into Users table
require_once __DIR__ . '/../helpers/DB.php';

$users = [
    ['username' => 'admin', 'email' => 'admin@example.com', 'password' => 'adminpass', 'role' => 'admin'],
    ['username' => 'alice', 'email' => 'alice@example.com', 'password' => 'password', 'role' => 'guest'],
    ['username' => 'bob', 'email' => 'bob@example.com', 'password' => 'password', 'role' => 'guest'],
    ['username' => 'staff', 'email' => 'staff@example.com', 'password' => 'staffpass', 'role' => 'staff'],
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

        $ins = $pdo->prepare('INSERT INTO Users (username, password_hash, account_email, role) VALUES (:u, :p, :e, :r)');
        $ins->execute([
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
