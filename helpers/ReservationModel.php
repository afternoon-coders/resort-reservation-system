<?php

require_once __DIR__ . '/BaseModel.php';

class ReservationModel extends BaseModel
{
    protected $table = 'Reservations';
    protected $primaryKey = 'reservation_id';

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (guest_id, cottage_id, check_in_date, check_out_date, total_amount, status) VALUES (:guest_id, :cottage_id, :check_in_date, :check_out_date, :total_amount, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':guest_id' => $data['guest_id'],
            ':cottage_id' => $data['room_id'] ?? $data['cottage_id'],
            ':check_in_date' => $data['check_in_date'],
            ':check_out_date' => $data['check_out_date'],
            ':total_amount' => $data['total_amount'] ?? ($data['total'] ?? 0),
            ':status' => $data['status'] ?? 'Pending',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function getById(int $id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getAll(array $opts = [])
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        $clauses = [];
        if (!empty($opts['guest_id'])) { $clauses[] = 'guest_id = :guest_id'; $params[':guest_id'] = $opts['guest_id']; }
        if (!empty($opts['room_id'])) { $clauses[] = 'cottage_id = :room_id'; $params[':room_id'] = $opts['room_id']; }
        if (!empty($opts['status'])) { $clauses[] = 'status = :status'; $params[':status'] = $opts['status']; }

        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

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
        if (isset($data['room_id'])) { $fields[] = 'cottage_id = :room_id'; $params[':room_id'] = $data['room_id']; }
        if (isset($data['check_in_date'])) { $fields[] = 'check_in_date = :check_in_date'; $params[':check_in_date'] = $data['check_in_date']; }
        if (isset($data['check_out_date'])) { $fields[] = 'check_out_date = :check_out_date'; $params[':check_out_date'] = $data['check_out_date']; }
        if (isset($data['status'])) { $fields[] = 'status = :status'; $params[':status'] = $data['status']; }

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
