<?php

require_once __DIR__ . '/BaseModel.php';

class GuestModel extends BaseModel
{
    protected $table = 'Guests';
    protected $primaryKey = 'guest_id';

    public function create(array $data)
    {
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $email = $data['email'] ?? $data['contact_email'] ?? null;
        $phone = $data['phone'] ?? $data['phone_number'] ?? null;
        $address = $data['address'] ?? null;

        if (empty($firstName) && empty($lastName) && !empty($data['name'])) {
            $parts = preg_split('/\s+/', trim($data['name']), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        $sql = "INSERT INTO {$this->table} (first_name, last_name, email, phone_number, address) VALUES (:first_name, :last_name, :email, :phone_number, :address)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':phone_number' => $phone,
            ':address' => $address,
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
        if (!empty($opts['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$opts['limit'];
        }

        $stmt = $this->pdo->prepare($sql);

        if (isset($params[':limit'])) {
            $stmt->bindValue(':limit', $params[':limit'], PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['first_name'])) { $fields[] = 'first_name = :first_name'; $params[':first_name'] = $data['first_name']; }
        if (isset($data['last_name'])) { $fields[] = 'last_name = :last_name'; $params[':last_name'] = $data['last_name']; }
        if (isset($data['email'])) { $fields[] = 'email = :email'; $params[':email'] = $data['email']; }
        if (isset($data['phone'])) { $fields[] = 'phone_number = :phone_number'; $params[':phone_number'] = $data['phone']; }
        if (isset($data['address'])) { $fields[] = 'address = :address'; $params[':address'] = $data['address']; }

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
