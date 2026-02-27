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
    ['number' => '101', 'name' => 'A Frame', 'price' => 6000, 'max_occupancy' => 4, 'is_available' => 1, 'types' => ['A Frame']],
    ['number' => '102', 'name' => 'Cottage', 'price' => 2000, 'max_occupancy' => 2, 'is_available' => 1, 'types' => ['Cottage']],
    ['number' => '103', 'name' => 'Bird House', 'price' => 1500, 'max_occupancy' => 2, 'is_available' => 1, 'types' => ['Bird House']],
    ['number' => '104', 'name' => 'Tree House', 'price' => 1500, 'max_occupancy' => 2, 'is_available' => 1, 'types' => ['Tree House']],
];

try {
    $pdo = DB::getPDO();

    // Insert types
    foreach ($types as $t) {
        $s = $pdo->prepare('SELECT type_id FROM Cottage_Types WHERE type_name = :n LIMIT 1');
        $s->execute([':n' => $t['name']]);
        if ($s->fetch()) {
            echo "Type exists: {$t['name']}\n";
            continue;
        }
        $ins = $pdo->prepare('INSERT INTO Cottage_Types (type_name, description) VALUES (:n, :d)');
        $ins->execute([':n' => $t['name'], ':d' => $t['desc']]);
        echo "Inserted type: {$t['name']}\n";
    }

    // Insert cottages and map types
    foreach ($cottages as $c) {
        $s = $pdo->prepare('SELECT cottage_id FROM Cottages WHERE cottage_number = :num LIMIT 1');
        $s->execute([':num' => $c['number']]);
        $row = $s->fetch();
        if ($row) {
            $cid = (int)$row['cottage_id'];
            echo "Cottage exists: {$c['number']}\n";
        } else {
            $ins = $pdo->prepare('INSERT INTO Cottages (cottage_number, name, base_price, max_occupancy, is_available) VALUES (:num, :name, :price, :max, :avail)');
            $ins->execute([
                ':num' => $c['number'],
                ':name' => $c['name'],
                ':price' => $c['price'],
                ':max' => $c['max_occupancy'],
                ':avail' => $c['is_available'],
            ]);
            $cid = (int)$pdo->lastInsertId();
            echo "Inserted cottage: {$c['number']} ({$c['name']})\n";
        }

        // map types
        foreach ($c['types'] as $tname) {
            $tq = $pdo->prepare('SELECT type_id FROM Cottage_Types WHERE type_name = :n LIMIT 1');
            $tq->execute([':n' => $tname]);
            $tr = $tq->fetch();
            if (!$tr) continue;
            $tid = (int)$tr['type_id'];

            $mq = $pdo->prepare('SELECT 1 FROM Cottage_Type_Mapping WHERE cottage_id = :cid AND type_id = :tid LIMIT 1');
            $mq->execute([':cid' => $cid, ':tid' => $tid]);
            if ($mq->fetch()) {
                continue;
            }
            $insm = $pdo->prepare('INSERT INTO Cottage_Type_Mapping (cottage_id, type_id) VALUES (:cid, :tid)');
            $insm->execute([':cid' => $cid, ':tid' => $tid]);
            echo "Mapped cottage {$c['number']} -> {$tname}\n";
        }
    }

    echo "Cottages seeding complete.\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
