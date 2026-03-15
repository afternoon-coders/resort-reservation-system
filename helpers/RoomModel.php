<?php

require_once __DIR__ . '/BaseModel.php';

class RoomModel extends BaseModel
{
    // Adapted to new schema: Cottages table
    protected string $table = 'Cottages';
    protected string $primaryKey = 'cottage_id';
    private const DEFAULT_CHECKIN_TIME = '15:00:00';
    private const DEFAULT_CHECKOUT_TIME = '11:00:00';

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (cottage_number, type_id, base_price, max_occupancy, status) VALUES (:cottage_number, :type_id, :base_price, :max_occupancy, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cottage_number' => $data['room_number'] ?? $data['cottage_number'] ?? null,
            ':type_id' => $data['type_id'] ?? 1, // Default to first type if not provided
            ':base_price' => $data['price_per_night'] ?? $data['base_price'] ?? 0.00,
            ':max_occupancy' => $data['number_of_beds'] ?? $data['max_occupancy'] ?? 1,
            ':status' => $data['status'] ?? 'Available',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function getById(int $id)
    {
        $sql = "SELECT c.*, t.type_name as name, t.description 
                FROM {$this->table} c 
                JOIN Cottage_Types t ON c.type_id = t.type_id 
                WHERE c.{$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getAll(array $opts = [])
    {
        $sql = "SELECT c.*, t.type_name as name, t.description 
                FROM {$this->table} c 
                JOIN Cottage_Types t ON c.type_id = t.type_id";
        $params = [];
        $where = [];

        if (isset($opts['status'])) {
            $where[] = "c.status = :status";
            $params[':status'] = $opts['status'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        if (!empty($opts['limit'])) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->pdo->prepare($sql);

        if (isset($params[':status'])) {
            $stmt->bindValue(':status', $params[':status']);
        }
        if (!empty($opts['limit'])) {
            $stmt->bindValue(':limit', (int)$opts['limit'], PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAvailableTypes(string $checkIn, string $checkOut)
    {
        $checkInDateTime = $this->normalizeStayDateTime($checkIn, self::DEFAULT_CHECKIN_TIME);
        $checkOutDateTime = $this->normalizeStayDateTime($checkOut, self::DEFAULT_CHECKOUT_TIME);

        $sql = "SELECT DISTINCT t.type_id, t.type_name as name, t.description, c.base_price, c.max_occupancy
                FROM Cottages c
                JOIN Cottage_Types t ON c.type_id = t.type_id
                WHERE c.status = 'Available'
                AND c.cottage_id NOT IN (
                    SELECT ri.cottage_id 
                    FROM Reservation_Items ri
                    JOIN Reservations r ON ri.reservation_id = r.reservation_id
                    WHERE r.status NOT IN ('Cancelled')
                    AND (r.status != 'Pending' OR r.token_expires_at > NOW())
                    AND r.check_in_date < :check_out
                    AND r.check_out_date > :check_in
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':check_in' => $checkInDateTime,
            ':check_out' => $checkOutDateTime
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function normalizeStayDateTime(string $value, string $defaultTime): string
    {
        $raw = trim($value);
        if ($raw === '') {
            throw new InvalidArgumentException('Invalid date range input.');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return $raw . ' ' . $defaultTime;
        }

        $parsed = date_create($raw);
        if ($parsed === false) {
            throw new InvalidArgumentException('Invalid date range format.');
        }

        return $parsed->format('Y-m-d H:i:s');
    }

    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['room_number'])) { $fields[] = 'cottage_number = :cottage_number'; $params[':cottage_number'] = $data['room_number']; }
        if (isset($data['type_id'])) { $fields[] = 'type_id = :type_id'; $params[':type_id'] = $data['type_id']; }
        if (isset($data['price_per_night'])) { $fields[] = 'base_price = :base_price'; $params[':base_price'] = $data['price_per_night']; }
        if (isset($data['status'])) { $fields[] = 'status = :status'; $params[':status'] = $data['status']; }
        if (isset($data['number_of_beds'])) { $fields[] = 'max_occupancy = :max_occupancy'; $params[':max_occupancy'] = $data['number_of_beds']; }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
