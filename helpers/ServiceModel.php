<?php

require_once __DIR__ . '/BaseModel.php';

class ServiceModel extends BaseModel
{
    protected string $table = 'services';
    protected string $primaryKey = 'service_id';

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (service_name, price) VALUES (:service_name, :price)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':service_name' => $data['service_name'] ?? null,
            ':price' => $data['price'] ?? 0,
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
        if (!empty($opts['limit'])) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->pdo->prepare($sql);
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

        if (isset($data['service_name'])) { $fields[] = 'service_name = :service_name'; $params[':service_name'] = $data['service_name']; }
        if (isset($data['price'])) { $fields[] = 'price = :price'; $params[':price'] = $data['price']; }

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
