<?php

require_once __DIR__ . '/BaseModel.php';

class ReservationModel extends BaseModel
{
    protected $table = 'Reservations';
    protected $primaryKey = 'reservation_id';

    public function create(array $data)
    {
        try {
            $this->pdo->beginTransaction();

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $sql = "INSERT INTO {$this->table} (guest_id, check_in_date, check_out_date, total_amount, status, notes, confirmation_token, token_expires_at) 
                    VALUES (:guest_id, :check_in_date, :check_out_date, :total_amount, :status, :notes, :token, :expires)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':guest_id' => $data['guest_id'],
                ':check_in_date' => $data['check_in_date'],
                ':check_out_date' => $data['check_out_date'],
                ':total_amount' => $data['total_amount'] ?? ($data['total'] ?? 0.00),
                ':status' => $data['status'] ?? 'Pending',
                ':notes' => $data['notes'] ?? null,
                ':token' => $token,
                ':expires' => $expiresAt
            ]);

            $reservationId = (int)$this->pdo->lastInsertId();
            
            // Attach token to return data for mailing
            $this->last_token = $token;

            // Handle Reservation Items (cottages)
            $cottages = [];
            if (isset($data['cottage_id'])) {
                $cottages[] = ['id' => $data['cottage_id'], 'price' => $data['price_at_booking'] ?? ($data['total_amount'] ?? 0)];
            } elseif (isset($data['room_id'])) {
                $cottages[] = ['id' => $data['room_id'], 'price' => $data['price_at_booking'] ?? ($data['total_amount'] ?? 0)];
            } elseif (isset($data['items']) && is_array($data['items'])) {
                $cottages = $data['items'];
            }

            $itemSql = "INSERT INTO Reservation_Items (reservation_id, cottage_id, price_at_booking) VALUES (:rid, :cid, :price)";
            $itemStmt = $this->pdo->prepare($itemSql);

            foreach ($cottages as $c) {
                $itemStmt->execute([
                    ':rid' => $reservationId,
                    ':cid' => $c['id'] ?? $c['cottage_id'],
                    ':price' => $c['price'] ?? $c['price_at_booking'] ?? 0.00,
                ]);
            }

            $this->pdo->commit();
            return $reservationId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function confirmByToken(string $token)
    {
        $sql = "UPDATE {$this->table} SET status = 'Confirmed', confirmation_token = NULL, token_expires_at = NULL 
                WHERE confirmation_token = :token AND token_expires_at > NOW() AND status = 'Pending'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->rowCount() > 0;
    }

    public function getLastToken() {
        return $this->last_token ?? null;
    }

    public function getById(int $id)
    {
        $sql = "SELECT r.*, g.first_name, g.last_name, g.email 
                FROM {$this->table} r 
                JOIN Guests g ON r.guest_id = g.guest_id 
                WHERE r.{$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();

        if ($res) {
            $itemSql = "SELECT ri.*, c.cottage_number, t.type_name 
                        FROM Reservation_Items ri 
                        JOIN Cottages c ON ri.cottage_id = c.cottage_id 
                        JOIN Cottage_Types t ON c.type_id = t.type_id 
                        WHERE ri.reservation_id = :rid";
            $itemStmt = $this->pdo->prepare($itemSql);
            $itemStmt->execute([':rid' => $id]);
            $res['items'] = $itemStmt->fetchAll();
        }

        return $res;
    }

    public function getAll(array $opts = [])
    {
        $sql = "SELECT r.*, g.first_name, g.last_name FROM {$this->table} r JOIN Guests g ON r.guest_id = g.guest_id";
        $params = [];
        $where = [];

        if (!empty($opts['guest_id'])) { $where[] = 'r.guest_id = :guest_id'; $params[':guest_id'] = $opts['guest_id']; }
        if (!empty($opts['status'])) { $where[] = 'r.status = :status'; $params[':status'] = $opts['status']; }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY r.booking_date DESC";

        if (!empty($opts['limit'])) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        if (!empty($opts['limit'])) {
            $stmt->bindValue(':limit', (int)$opts['limit'], PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['guest_id'])) { $fields[] = 'guest_id = :guest_id'; $params[':guest_id'] = $data['guest_id']; }
        if (isset($data['check_in_date'])) { $fields[] = 'check_in_date = :check_in_date'; $params[':check_in_date'] = $data['check_in_date']; }
        if (isset($data['check_out_date'])) { $fields[] = 'check_out_date = :check_out_date'; $params[':check_out_date'] = $data['check_out_date']; }
        if (isset($data['status'])) { $fields[] = 'status = :status'; $params[':status'] = $data['status']; }
        if (isset($data['total_amount'])) { $fields[] = 'total_amount = :total_amount'; $params[':total_amount'] = $data['total_amount']; }
        if (isset($data['notes'])) { $fields[] = 'notes = :notes'; $params[':notes'] = $data['notes']; }

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
