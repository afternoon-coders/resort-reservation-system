<?php
// Seed cottage types, cottages and mappings
require_once __DIR__ . '/../helpers/DB.php';

$types = [
    ['name' => 'A Frame', 'desc' => 'A-frame style cabin'],
    ['name' => 'Cottage', 'desc' => 'Cozy cottage'],
    ['name' => 'Bird House', 'desc' => 'Unique bird house'],
    ['name' => 'Tree House', 'desc' => 'Tree house with view'],
];

$cottages = [
    ['number' => '101', 'type' => 'A Frame', 'price' => 6000, 'max_occupancy' => 4, 'status' => 'Available'],
    ['number' => '102', 'type' => 'Cottage', 'price' => 2000, 'max_occupancy' => 2, 'status' => 'Available'],
    ['number' => '103', 'type' => 'Bird House', 'price' => 1500, 'max_occupancy' => 2, 'status' => 'Available'],
    ['number' => '104', 'type' => 'Tree House', 'price' => 1500, 'max_occupancy' => 2, 'status' => 'Available'],
    ['number' => '105', 'type' => 'A Frame', 'price' => 6000, 'max_occupancy' => 4, 'status' => 'Available'],
    ['number' => '106', 'type' => 'Cottage', 'price' => 2000, 'max_occupancy' => 2, 'status' => 'Available'],
    ['number' => '107', 'type' => 'Bird House', 'price' => 1500, 'max_occupancy' => 2, 'status' => 'Available'],
    ['number' => '108', 'type' => 'Tree House', 'price' => 1500, 'max_occupancy' => 2, 'status' => 'Available'],
];

try {
    $pdo = DB::getPDO();

    // Insert types
    $typeMap = [];
    foreach ($types as $t) {
        $s = $pdo->prepare('SELECT type_id FROM Cottage_Types WHERE type_name = :n LIMIT 1');
        $s->execute([':n' => $t['name']]);
        $row = $s->fetch();
        if ($row) {
            echo "Type exists: {$t['name']}\n";
            $typeMap[$t['name']] = $row['type_id'];
            continue;
        }
        $ins = $pdo->prepare('INSERT INTO Cottage_Types (type_name, description) VALUES (:n, :d)');
        $ins->execute([':n' => $t['name'], ':d' => $t['desc']]);
        $typeMap[$t['name']] = $pdo->lastInsertId();
        echo "Inserted type: {$t['name']}\n";
    }

    // Insert cottages
    foreach ($cottages as $c) {
        $s = $pdo->prepare('SELECT cottage_id FROM Cottages WHERE cottage_number = :num LIMIT 1');
        $s->execute([':num' => $c['number']]);
        $row = $s->fetch();
        if ($row) {
            echo "Cottage exists: {$c['number']}\n";
            continue;
        }

        $typeId = $typeMap[$c['type']] ?? null;
        if (!$typeId) {
            echo "Type NOT found for cottage: {$c['number']} ({$c['type']})\n";
            continue;
        }

        $ins = $pdo->prepare('INSERT INTO Cottages (cottage_number, type_id, base_price, max_occupancy, status) VALUES (:num, :tid, :price, :max, :status)');
        $ins->execute([
            ':num' => $c['number'],
            ':tid' => $typeId,
            ':price' => $c['price'],
            ':max' => $c['max_occupancy'],
            ':status' => $c['status'],
        ]);
        echo "Inserted cottage: {$c['number']} ({$c['type']})\n";
    }

    echo "Cottages seeding complete.\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
