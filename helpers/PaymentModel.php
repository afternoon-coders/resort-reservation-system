<?php

require_once __DIR__ . '/BaseModel.php';

class PaymentModel extends BaseModel
{
    protected string $table = 'Payments';
    protected string $primaryKey = 'payment_id';

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (reservation_id, amount_paid, payment_method, payment_date, payment_status, transaction_ref) VALUES (:reservation_id, :amount_paid, :payment_method, :payment_date, :payment_status, :transaction_ref)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':reservation_id' => $data['reservation_id'],
            ':amount_paid' => $data['amount'] ?? $data['amount_paid'] ?? 0.00,
            ':payment_method' => $data['payment_method'] ?? 'Cash',
            ':payment_date' => $data['payment_date'] ?? date('Y-m-d H:i:s'),
            ':payment_status' => $data['payment_status'] ?? 'Completed',
            ':transaction_ref' => $data['transaction_ref'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function getById(int $id)
    {
        $sql = "SELECT p.*, r.guest_id, g.first_name, g.last_name 
                FROM {$this->table} p 
                JOIN Reservations r ON p.reservation_id = r.reservation_id 
                JOIN Guests g ON r.guest_id = g.guest_id 
                WHERE p.{$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getAll(array $opts = [])
    {
        $sql = "SELECT p.*, g.first_name, g.last_name 
                FROM {$this->table} p 
                JOIN Reservations r ON p.reservation_id = r.reservation_id 
                JOIN Guests g ON r.guest_id = g.guest_id";
        $params = [];
        $where = [];

        if (!empty($opts['reservation_id'])) { $where[] = 'p.reservation_id = :reservation_id'; $params[':reservation_id'] = $opts['reservation_id']; }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY p.payment_date DESC";

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

        if (isset($data['reservation_id'])) { $fields[] = 'reservation_id = :reservation_id'; $params[':reservation_id'] = $data['reservation_id']; }
        if (isset($data['amount'])) { $fields[] = 'amount_paid = :amount_paid'; $params[':amount_paid'] = $data['amount']; }
        if (isset($data['payment_method'])) { $fields[] = 'payment_method = :payment_method'; $params[':payment_method'] = $data['payment_method']; }
        if (isset($data['payment_date'])) { $fields[] = 'payment_date = :payment_date'; $params[':payment_date'] = $data['payment_date']; }
        if (isset($data['payment_status'])) { $fields[] = 'payment_status = :payment_status'; $params[':payment_status'] = $data['payment_status']; }
        if (isset($data['transaction_ref'])) { $fields[] = 'transaction_ref = :transaction_ref'; $params[':transaction_ref'] = $data['transaction_ref']; }

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
