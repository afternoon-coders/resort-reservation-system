<?php

require_once __DIR__ . '/BaseModel.php';

class GuestModel extends BaseModel
{
    protected $table = 'Guests';
    protected $primaryKey = 'guest_id';

    public function create(array $data)
    {
        $userId = $data['user_id'] ?? null;
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $email = $data['email'] ?? $data['contact_email'] ?? null;
        $phone = $data['phone'] ?? $data['phone_number'] ?? null;

        // If user_id is provided, inherit missing info
        if ($userId) {
            require_once __DIR__ . '/UserModel.php';
            $userModel = new UserModel();
            $user = $userModel->getById($userId);
            
            if ($user) {
                if (empty($firstName) && empty($lastName) && !empty($data['name'])) {
                    $parts = preg_split('/\s+/', trim($data['name']), 2);
                    $firstName = $parts[0] ?? '';
                    $lastName = $parts[1] ?? '';
                }

                if (empty($firstName)) $firstName = $user['first_name'];
                if (empty($lastName)) $lastName = $user['last_name'];
                if (empty($email)) $email = $user['account_email'];
            }
        } else if (!empty($data['name'])) {
            $parts = preg_split('/\s+/', trim($data['name']), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        $sql = "INSERT INTO {$this->table} (user_id, first_name, last_name, contact_email, phone_number) VALUES (:user_id, :first_name, :last_name, :contact_email, :phone_number)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':contact_email' => $email,
            ':phone_number' => $phone,
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

    public function getByUserId(int $userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
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

        if (isset($data['user_id'])) { $fields[] = 'user_id = :user_id'; $params[':user_id'] = $data['user_id']; }
        if (isset($data['first_name'])) { $fields[] = 'first_name = :first_name'; $params[':first_name'] = $data['first_name']; }
        if (isset($data['last_name'])) { $fields[] = 'last_name = :last_name'; $params[':last_name'] = $data['last_name']; }
        if (isset($data['email'])) { $fields[] = 'contact_email = :contact_email'; $params[':contact_email'] = $data['email']; }
        if (isset($data['phone'])) { $fields[] = 'phone_number = :phone_number'; $params[':phone_number'] = $data['phone']; }

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
