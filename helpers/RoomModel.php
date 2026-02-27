<?php

require_once __DIR__ . '/BaseModel.php';

class RoomModel extends BaseModel
{
    // Adapted to new schema: Cottages table
    protected $table = 'Cottages';
    protected $primaryKey = 'cottage_id';

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (cottage_number, name, base_price, max_occupancy, is_available) VALUES (:cottage_number, :name, :base_price, :max_occupancy, :is_available)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cottage_number' => $data['room_number'] ?? $data['cottage_number'] ?? null,
            ':name' => $data['room_type'] ?? $data['name'] ?? null,
            ':base_price' => $data['price_per_night'] ?? $data['base_price'] ?? 0,
            ':max_occupancy' => $data['number_of_beds'] ?? $data['max_occupancy'] ?? 1,
            ':is_available' => isset($data['status']) ? ($data['status'] === 'available' ? 1 : 0) : 1,
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

        if (isset($opts['status'])) {
            $sql .= " WHERE is_available = :is_available";
            $params[':is_available'] = $opts['status'] === 'available' ? 1 : 0;
        }

        if (!empty($opts['limit'])) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->pdo->prepare($sql);

        if (isset($params[':is_available'])) {
            $stmt->bindValue(':is_available', $params[':is_available'], PDO::PARAM_INT);
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

        if (isset($data['room_number'])) { $fields[] = 'cottage_number = :cottage_number'; $params[':cottage_number'] = $data['room_number']; }
        if (isset($data['room_type'])) { $fields[] = 'name = :name'; $params[':name'] = $data['room_type']; }
        if (isset($data['price_per_night'])) { $fields[] = 'base_price = :base_price'; $params[':base_price'] = $data['price_per_night']; }
        if (isset($data['status'])) { $fields[] = 'is_available = :is_available'; $params[':is_available'] = ($data['status'] === 'available') ? 1 : 0; }
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
