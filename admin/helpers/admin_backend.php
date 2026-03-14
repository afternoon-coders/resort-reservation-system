<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth_functions.php';
require_once __DIR__ . '/../../helpers/DB.php';

const ADMIN_CSRF_SESSION_KEY = 'admin_csrf_token';
const ADMIN_FLASH_SESSION_KEY = 'admin_flash';

function admin_bootstrap(): PDO
{
    requireLogin();
    requireAdmin();
    return DB::getPDO();
}

function admin_get_csrf_token(): string
{
    if (empty($_SESSION[ADMIN_CSRF_SESSION_KEY])) {
        try {
            $_SESSION[ADMIN_CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $_SESSION[ADMIN_CSRF_SESSION_KEY] = hash('sha256', uniqid('', true) . microtime(true));
        }
    }

    return (string)$_SESSION[ADMIN_CSRF_SESSION_KEY];
}

function admin_require_csrf_token(?string $submittedToken): void
{
    $sessionToken = isset($_SESSION[ADMIN_CSRF_SESSION_KEY]) ? (string)$_SESSION[ADMIN_CSRF_SESSION_KEY] : '';
    $submitted = trim((string)$submittedToken);

    if ($sessionToken === '' || $submitted === '' || !hash_equals($sessionToken, $submitted)) {
        throw new RuntimeException('Security validation failed. Please refresh the page and try again.');
    }
}

function admin_set_flash(string $type, string $message): void
{
    $_SESSION[ADMIN_FLASH_SESSION_KEY] = [
        'type' => $type,
        'message' => $message,
    ];
}

function admin_pop_flash(): ?array
{
    if (!isset($_SESSION[ADMIN_FLASH_SESSION_KEY]) || !is_array($_SESSION[ADMIN_FLASH_SESSION_KEY])) {
        return null;
    }

    $flash = $_SESSION[ADMIN_FLASH_SESSION_KEY];
    unset($_SESSION[ADMIN_FLASH_SESSION_KEY]);

    if (!isset($flash['type'], $flash['message'])) {
        return null;
    }

    return [
        'type' => (string)$flash['type'],
        'message' => (string)$flash['message'],
    ];
}

function admin_redirect_to_page(string $page, array $params = []): void
{
    $queryParams = array_merge(['page' => $page], $params);
    $queryParams = array_filter($queryParams, static function ($value) {
        return $value !== null && $value !== '';
    });

    $url = 'index.php';
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }

    if (!headers_sent()) {
        // 303 is preferred for redirect-after-POST.
        header('Location: ' . $url, true, 303);
        exit;
    }

    $jsUrl = json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    if ($jsUrl === false) {
        $jsUrl = '"index.php"';
    }

    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo '<script>window.location.href=' . $jsUrl . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $safeUrl . '"></noscript>';
    exit;
}

function admin_positive_int($value): ?int
{
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }

    if (is_string($value) && ctype_digit($value)) {
        $parsed = (int)$value;
        return $parsed > 0 ? $parsed : null;
    }

    return null;
}

function admin_normalize_enum(?string $value, array $allowed): ?string
{
    if ($value === null) {
        return null;
    }

    $normalizedValue = strtolower(str_replace([' ', '_'], '-', trim($value)));
    foreach ($allowed as $candidate) {
        $normalizedCandidate = strtolower(str_replace(' ', '-', $candidate));
        if ($normalizedValue === $normalizedCandidate) {
            return $candidate;
        }
    }

    return null;
}

function admin_reservation_statuses(): array
{
    return ['Pending', 'Confirmed', 'Checked-In', 'Checked-Out', 'Cancelled'];
}

function admin_cottage_statuses(): array
{
    return ['Available', 'Occupied', 'Maintenance', 'Out of Order'];
}

function admin_dispatch_action(PDO $pdo, string $action, array $input): array
{
    switch ($action) {
        case 'update_reservation_status': {
            $reservationId = admin_positive_int($input['reservation_id'] ?? null);
            $status = admin_normalize_enum($input['status'] ?? null, admin_reservation_statuses());

            if ($reservationId === null || $status === null) {
                return ['ok' => false, 'message' => 'Invalid reservation status update request.'];
            }

            return admin_update_reservation_status($pdo, $reservationId, $status);
        }

        case 'delete_reservation': {
            $reservationId = admin_positive_int($input['reservation_id'] ?? null);
            if ($reservationId === null) {
                return ['ok' => false, 'message' => 'Invalid reservation id.'];
            }

            return admin_delete_reservation($pdo, $reservationId);
        }

        case 'delete_user': {
            $userId = admin_positive_int($input['user_id'] ?? null);
            if ($userId === null) {
                return ['ok' => false, 'message' => 'Invalid user id.'];
            }

            return admin_delete_user($pdo, $userId);
        }

        case 'update_room_status':
        case 'update_status': {
            $cottageId = admin_positive_int($input['room_id'] ?? ($input['cottage_id'] ?? null));
            $status = admin_normalize_enum($input['status'] ?? null, admin_cottage_statuses());

            if ($cottageId === null || $status === null) {
                return ['ok' => false, 'message' => 'Invalid cottage status update request.'];
            }

            return admin_update_cottage_status($pdo, $cottageId, $status);
        }

        case 'delete_cottage': {
            $cottageId = admin_positive_int($input['cottage_id'] ?? null);
            if ($cottageId === null) {
                return ['ok' => false, 'message' => 'Invalid cottage id.'];
            }

            return admin_delete_cottage($pdo, $cottageId);
        }

        case 'create_cottage': {
            return admin_create_cottage($pdo, $input);
        }

        case 'process_payment': {
            return admin_process_payment($pdo, $input);
        }

        default:
            return ['ok' => false, 'message' => 'Unsupported admin action.'];
    }
}

function admin_update_reservation_status(PDO $pdo, int $reservationId, string $status): array
{
    $existsStmt = $pdo->prepare('SELECT reservation_id FROM Reservations WHERE reservation_id = :id LIMIT 1');
    $existsStmt->execute([':id' => $reservationId]);
    if (!$existsStmt->fetchColumn()) {
        return ['ok' => false, 'message' => 'Reservation not found.'];
    }

    $updateStmt = $pdo->prepare('UPDATE Reservations SET status = :status WHERE reservation_id = :id');
    $updateStmt->execute([
        ':status' => $status,
        ':id' => $reservationId,
    ]);

    if ($status === 'Checked-In') {
        $occupyStmt = $pdo->prepare(
            "UPDATE Cottages c
             INNER JOIN Reservation_Items ri ON ri.cottage_id = c.cottage_id
             SET c.status = 'Occupied'
             WHERE ri.reservation_id = :reservation_id
             AND c.status NOT IN ('Maintenance', 'Out of Order')"
        );
        $occupyStmt->execute([':reservation_id' => $reservationId]);
    }

    if ($status === 'Checked-Out' || $status === 'Cancelled') {
        $cottageIds = admin_get_reservation_cottage_ids($pdo, $reservationId);
        foreach ($cottageIds as $cottageId) {
            admin_release_cottage_if_idle($pdo, $cottageId);
        }
    }

    return ['ok' => true, 'message' => 'Reservation status updated.'];
}

function admin_delete_reservation(PDO $pdo, int $reservationId): array
{
    $pdo->beginTransaction();
    try {
        $cottageIds = admin_get_reservation_cottage_ids($pdo, $reservationId);

        $deleteStmt = $pdo->prepare('DELETE FROM Reservations WHERE reservation_id = :id');
        $deleteStmt->execute([':id' => $reservationId]);

        if ($deleteStmt->rowCount() === 0) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Reservation not found.'];
        }

        foreach ($cottageIds as $cottageId) {
            admin_release_cottage_if_idle($pdo, $cottageId);
        }

        $pdo->commit();
        return ['ok' => true, 'message' => 'Reservation deleted.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function admin_delete_user(PDO $pdo, int $userId): array
{
    $currentUserId = admin_positive_int($_SESSION['user_id'] ?? null);
    if ($currentUserId !== null && $currentUserId === $userId) {
        return ['ok' => false, 'message' => 'You cannot delete your own admin account while logged in.'];
    }

    $userStmt = $pdo->prepare('SELECT role FROM Users WHERE user_id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['ok' => false, 'message' => 'User not found.'];
    }

    if (($user['role'] ?? '') === 'admin') {
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'admin'")->fetchColumn();
        if ($adminCount <= 1) {
            return ['ok' => false, 'message' => 'Cannot delete the last admin account.'];
        }
    }

    $deleteStmt = $pdo->prepare('DELETE FROM Users WHERE user_id = :id');
    $deleteStmt->execute([':id' => $userId]);

    return ['ok' => true, 'message' => 'User deleted.'];
}

function admin_update_cottage_status(PDO $pdo, int $cottageId, string $status): array
{
    $updateStmt = $pdo->prepare('UPDATE Cottages SET status = :status WHERE cottage_id = :id');
    $updateStmt->execute([
        ':status' => $status,
        ':id' => $cottageId,
    ]);

    if ($updateStmt->rowCount() === 0) {
        $existsStmt = $pdo->prepare('SELECT cottage_id FROM Cottages WHERE cottage_id = :id LIMIT 1');
        $existsStmt->execute([':id' => $cottageId]);
        if (!$existsStmt->fetchColumn()) {
            return ['ok' => false, 'message' => 'Cottage not found.'];
        }
    }

    return ['ok' => true, 'message' => 'Cottage status updated.'];
}

function admin_delete_cottage(PDO $pdo, int $cottageId): array
{
    $pdo->beginTransaction();
    try {
        $existsStmt = $pdo->prepare('SELECT cottage_id FROM Cottages WHERE cottage_id = :id LIMIT 1');
        $existsStmt->execute([':id' => $cottageId]);
        if (!$existsStmt->fetchColumn()) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Cottage not found.'];
        }

        $historyStmt = $pdo->prepare('SELECT COUNT(*) FROM Reservation_Items WHERE cottage_id = :id');
        $historyStmt->execute([':id' => $cottageId]);
        $historyCount = (int)$historyStmt->fetchColumn();

        if ($historyCount > 0) {
            $statusStmt = $pdo->prepare("UPDATE Cottages SET status = 'Out of Order' WHERE cottage_id = :id");
            $statusStmt->execute([':id' => $cottageId]);

            $pdo->commit();
            return [
                'ok' => true,
                'message' => 'Cottage has reservation history and was marked Out of Order instead of being deleted.',
            ];
        }

        $deleteStmt = $pdo->prepare('DELETE FROM Cottages WHERE cottage_id = :id');
        $deleteStmt->execute([':id' => $cottageId]);

        $pdo->commit();
        return ['ok' => true, 'message' => 'Cottage deleted successfully.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function admin_create_cottage(PDO $pdo, array $input): array
{
    $validation = admin_validate_cottage_payload($pdo, $input);
    if (!$validation['ok']) {
        return [
            'ok' => false,
            'message' => implode(' ', $validation['errors']),
        ];
    }

    $data = $validation['data'];
    $stmt = $pdo->prepare(
        'INSERT INTO Cottages (cottage_number, type_id, base_price, max_occupancy, status, description)
         VALUES (:cottage_number, :type_id, :base_price, :max_occupancy, :status, :description)'
    );

    $stmt->execute([
        ':cottage_number' => $data['cottage_number'],
        ':type_id' => $data['type_id'],
        ':base_price' => $data['base_price'],
        ':max_occupancy' => $data['max_occupancy'],
        ':status' => $data['status'],
        ':description' => $data['description'],
    ]);

    return ['ok' => true, 'message' => 'Cottage added successfully.'];
}

function admin_update_cottage(PDO $pdo, int $cottageId, array $input): array
{
    $existsStmt = $pdo->prepare('SELECT cottage_id FROM Cottages WHERE cottage_id = :id LIMIT 1');
    $existsStmt->execute([':id' => $cottageId]);
    if (!$existsStmt->fetchColumn()) {
        return ['ok' => false, 'message' => 'Cottage not found.'];
    }

    $validation = admin_validate_cottage_payload($pdo, $input, $cottageId);
    if (!$validation['ok']) {
        return [
            'ok' => false,
            'message' => implode(' ', $validation['errors']),
        ];
    }

    $data = $validation['data'];
    $stmt = $pdo->prepare(
        'UPDATE Cottages
         SET cottage_number = :cottage_number,
             type_id = :type_id,
             base_price = :base_price,
             max_occupancy = :max_occupancy,
             status = :status,
             description = :description
         WHERE cottage_id = :cottage_id'
    );

    $stmt->execute([
        ':cottage_number' => $data['cottage_number'],
        ':type_id' => $data['type_id'],
        ':base_price' => $data['base_price'],
        ':max_occupancy' => $data['max_occupancy'],
        ':status' => $data['status'],
        ':description' => $data['description'],
        ':cottage_id' => $cottageId,
    ]);

    return ['ok' => true, 'message' => 'Cottage updated successfully.'];
}

function admin_validate_cottage_payload(PDO $pdo, array $input, ?int $excludeCottageId = null): array
{
    $errors = [];

    $cottageNumber = trim((string)($input['cottage_number'] ?? ''));
    $typeId = admin_positive_int($input['type_id'] ?? null);
    $basePriceRaw = $input['base_price'] ?? 0;
    $maxOccupancy = admin_positive_int($input['max_occupancy'] ?? 2);
    $status = admin_normalize_enum($input['status'] ?? 'Available', admin_cottage_statuses());
    $description = trim((string)($input['description'] ?? ''));

    if ($cottageNumber === '') {
        $errors[] = 'Cottage number is required.';
    } elseif (strlen($cottageNumber) > 20) {
        $errors[] = 'Cottage number must be 20 characters or less.';
    }

    if ($typeId === null) {
        $errors[] = 'A valid cottage type is required.';
    } else {
        $typeStmt = $pdo->prepare('SELECT type_id FROM Cottage_Types WHERE type_id = :id LIMIT 1');
        $typeStmt->execute([':id' => $typeId]);
        if (!$typeStmt->fetchColumn()) {
            $errors[] = 'Selected cottage type does not exist.';
        }
    }

    $basePrice = filter_var($basePriceRaw, FILTER_VALIDATE_FLOAT);
    if ($basePrice === false || $basePrice < 0) {
        $errors[] = 'Base price must be a valid non-negative number.';
    }

    if ($maxOccupancy === null || $maxOccupancy > 50) {
        $errors[] = 'Max occupancy must be between 1 and 50.';
    }

    if ($status === null) {
        $errors[] = 'Invalid cottage status.';
    }

    if (strlen($description) > 2000) {
        $errors[] = 'Description is too long.';
    }

    if ($cottageNumber !== '') {
        $duplicateSql = 'SELECT cottage_id FROM Cottages WHERE cottage_number = :cottage_number';
        $params = [':cottage_number' => $cottageNumber];

        if ($excludeCottageId !== null) {
            $duplicateSql .= ' AND cottage_id != :exclude_id';
            $params[':exclude_id'] = $excludeCottageId;
        }

        $duplicateSql .= ' LIMIT 1';
        $duplicateStmt = $pdo->prepare($duplicateSql);
        $duplicateStmt->execute($params);
        if ($duplicateStmt->fetchColumn()) {
            $errors[] = 'Cottage number already exists.';
        }
    }

    if (!empty($errors)) {
        return [
            'ok' => false,
            'errors' => $errors,
            'data' => [],
        ];
    }

    return [
        'ok' => true,
        'errors' => [],
        'data' => [
            'cottage_number' => $cottageNumber,
            'type_id' => $typeId,
            'base_price' => (float)$basePrice,
            'max_occupancy' => $maxOccupancy,
            'status' => $status,
            'description' => $description,
        ],
    ];
}

function admin_get_reservation_cottage_ids(PDO $pdo, int $reservationId): array
{
    $stmt = $pdo->prepare(
        'SELECT DISTINCT cottage_id
         FROM Reservation_Items
         WHERE reservation_id = :reservation_id'
    );
    $stmt->execute([':reservation_id' => $reservationId]);

    $ids = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $parsed = admin_positive_int($id);
        if ($parsed !== null) {
            $ids[] = $parsed;
        }
    }

    return $ids;
}

function admin_process_payment(PDO $pdo, array $input): array
{
    $reservationId = admin_positive_int($input['reservation_id'] ?? null);
    if ($reservationId === null) {
        return ['ok' => false, 'message' => 'Invalid reservation ID.'];
    }

    // Fetch reservation to validate current state
    $resStmt = $pdo->prepare(
        "SELECT r.reservation_id, r.status, r.total_amount,
                CONCAT(COALESCE(g.first_name,''), ' ', COALESCE(g.last_name,'')) AS guest_name
         FROM Reservations r
         JOIN Guests g ON r.guest_id = g.guest_id
         WHERE r.reservation_id = :id LIMIT 1"
    );
    $resStmt->execute([':id' => $reservationId]);
    $reservation = $resStmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        return ['ok' => false, 'message' => 'Reservation not found.'];
    }

    if ($reservation['status'] !== 'Confirmed') {
        return [
            'ok'      => false,
            'message' => "Payment can only be processed for Confirmed reservations. Current status: {$reservation['status']}.",
        ];
    }

    // Validate amount
    $amount = filter_var($input['amount_paid'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($amount === false || $amount <= 0) {
        return ['ok' => false, 'message' => 'Payment amount must be a positive number.'];
    }

    // Validate payment method
    $validMethods = ['Cash', 'Credit Card', 'PayPal', 'Bank Transfer'];
    $method = admin_normalize_enum($input['payment_method'] ?? null, $validMethods);
    if ($method === null) {
        return ['ok' => false, 'message' => 'Please select a valid payment method.'];
    }

    $transactionRef = trim((string)($input['transaction_ref'] ?? ''));
    if (strlen($transactionRef) > 100) {
        $transactionRef = substr($transactionRef, 0, 100);
    }
    $transactionRef = $transactionRef !== '' ? $transactionRef : null;

    // Record payment + check in inside a single transaction
    $pdo->beginTransaction();
    try {
        // Insert payment record
        $payStmt = $pdo->prepare(
            "INSERT INTO Payments (reservation_id, amount_paid, payment_method, payment_date, payment_status, transaction_ref)
             VALUES (:reservation_id, :amount_paid, :payment_method, NOW(), 'Completed', :transaction_ref)"
        );
        $payStmt->execute([
            ':reservation_id' => $reservationId,
            ':amount_paid'    => $amount,
            ':payment_method' => $method,
            ':transaction_ref' => $transactionRef,
        ]);

        // Update reservation to Checked-In
        $pdo->prepare("UPDATE Reservations SET status = 'Checked-In' WHERE reservation_id = :id")
            ->execute([':id' => $reservationId]);

        // Mark assigned cottages as Occupied
        $pdo->prepare(
            "UPDATE Cottages c
             INNER JOIN Reservation_Items ri ON ri.cottage_id = c.cottage_id
             SET c.status = 'Occupied'
             WHERE ri.reservation_id = :reservation_id
             AND c.status NOT IN ('Maintenance', 'Out of Order')"
        )->execute([':reservation_id' => $reservationId]);

        $pdo->commit();

        $guestName = trim((string)($reservation['guest_name'] ?? '')) ?: 'Guest';
        return [
            'ok'      => true,
            'message' => sprintf(
                'Payment of ₱%s recorded for %s. Reservation #%d is now Checked-In.',
                number_format((float)$amount, 2),
                $guestName,
                $reservationId
            ),
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function admin_release_cottage_if_idle(PDO $pdo, int $cottageId): void
{
    $activeStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM Reservation_Items ri
         INNER JOIN Reservations r ON r.reservation_id = ri.reservation_id
         WHERE ri.cottage_id = :cottage_id
         AND r.status = 'Checked-In'"
    );
    $activeStmt->execute([':cottage_id' => $cottageId]);

    if ((int)$activeStmt->fetchColumn() > 0) {
        return;
    }

    $releaseStmt = $pdo->prepare(
        "UPDATE Cottages
         SET status = 'Available'
         WHERE cottage_id = :cottage_id
         AND status = 'Occupied'"
    );
    $releaseStmt->execute([':cottage_id' => $cottageId]);
}
