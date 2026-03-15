<?php
// One-time migration: move reservation stay dates to DATETIME and add checkout timestamp tracking.
require_once __DIR__ . '/../helpers/DB.php';

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = :table_name
         AND COLUMN_NAME = :column_name"
    );
    $stmt->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);

    return ((int)$stmt->fetchColumn()) > 0;
}

function columnType(PDO $pdo, string $table, string $column): ?string
{
    $stmt = $pdo->prepare(
        "SELECT DATA_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = :table_name
         AND COLUMN_NAME = :column_name
         LIMIT 1"
    );
    $stmt->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);

    $type = $stmt->fetchColumn();
    return $type === false ? null : strtolower((string)$type);
}

try {
    $pdo = DB::getPDO();

    echo "Starting reservation timestamp migration...\n";

    if (columnExists($pdo, 'Reservations', 'check_in_date') && columnType($pdo, 'Reservations', 'check_in_date') !== 'datetime') {
        $pdo->exec("ALTER TABLE Reservations MODIFY COLUMN check_in_date DATETIME NOT NULL");
        echo "- Converted check_in_date to DATETIME\n";
    } else {
        echo "- check_in_date is already DATETIME\n";
    }

    if (columnExists($pdo, 'Reservations', 'check_out_date') && columnType($pdo, 'Reservations', 'check_out_date') !== 'datetime') {
        $pdo->exec("ALTER TABLE Reservations MODIFY COLUMN check_out_date DATETIME NOT NULL");
        echo "- Converted check_out_date to DATETIME\n";
    } else {
        echo "- check_out_date is already DATETIME\n";
    }

    if (!columnExists($pdo, 'Reservations', 'checked_in_at')) {
        $pdo->exec("ALTER TABLE Reservations ADD COLUMN checked_in_at DATETIME NULL AFTER check_out_date");
        echo "- Added checked_in_at column\n";
    } else {
        echo "- checked_in_at column already exists\n";
    }

    if (!columnExists($pdo, 'Reservations', 'checked_out_at')) {
        $pdo->exec("ALTER TABLE Reservations ADD COLUMN checked_out_at DATETIME NULL AFTER checked_in_at");
        echo "- Added checked_out_at column\n";
    } else {
        echo "- checked_out_at column already exists\n";
    }

    // Normalize legacy rows that were date-only midnight values.
    $pdo->exec("UPDATE Reservations SET check_in_date = TIMESTAMP(DATE(check_in_date), '15:00:00') WHERE TIME(check_in_date) = '00:00:00'");
    $pdo->exec("UPDATE Reservations SET check_out_date = TIMESTAMP(DATE(check_out_date), '11:00:00') WHERE TIME(check_out_date) = '00:00:00'");
    echo "- Normalized midnight stay dates to check-in 15:00:00 and check-out 11:00:00\n";

    // Backfill check-in timestamps from payment time or scheduled check-in when missing.
    if (columnExists($pdo, 'Reservations', 'checked_in_at')) {
        $pdo->exec(
            "UPDATE Reservations r
             LEFT JOIN (
                 SELECT reservation_id, MIN(payment_date) AS first_payment_at
                 FROM Payments
                 WHERE payment_status = 'Completed'
                 GROUP BY reservation_id
             ) p ON p.reservation_id = r.reservation_id
             SET r.checked_in_at = COALESCE(r.checked_in_at, p.first_payment_at, r.check_in_date)
             WHERE r.status IN ('Checked-In', 'Checked-Out')
             AND r.checked_in_at IS NULL"
        );
        echo "- Backfilled checked_in_at where possible\n";
    }

    // Backfill checked-out timestamps for rows already checked out.
    if (columnExists($pdo, 'Reservations', 'checked_out_at')) {
        $pdo->exec(
            "UPDATE Reservations
             SET checked_out_at = COALESCE(checked_out_at, check_out_date)
             WHERE status = 'Checked-Out'
             AND checked_out_at IS NULL"
        );
        echo "- Backfilled checked_out_at for existing checked-out reservations\n";
    }

    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
