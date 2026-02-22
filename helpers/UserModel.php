<?php

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (username, password, email, role) VALUES (:username, :password, :email, :role)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':username' => $data['username'] ?? null,
            ':password' => password_hash($data['password'] ?? '', PASSWORD_BCRYPT),
            ':email' => $data['email'] ?? null,
            ':role' => $data['role'] ?? 'guest',
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

    public function getByUsername(string $username)
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    public function getByEmail(string $email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function getAll(array $opts = [])
    {
        $sql = "SELECT * FROM {$this->table}";

        $params = [];
        
        if (!empty($opts['role'])) {
            $sql .= " WHERE role = :role";
            $params[':role'] = $opts['role'];
        }

        if (!empty($opts['limit'])) {
            $sql .= (strpos($sql, 'WHERE') ? ' ' : ' ') . "LIMIT :limit";
            $params[':limit'] = (int)$opts['limit'];
        }

        $stmt = $this->pdo->prepare($sql);

        if (isset($params[':limit'])) {
            $stmt->bindValue(':limit', $params[':limit'], PDO::PARAM_INT);
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['username'])) { $fields[] = 'username = :username'; $params[':username'] = $data['username']; }
        if (isset($data['password'])) { $fields[] = 'password = :password'; $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT); }
        if (isset($data['email'])) { $fields[] = 'email = :email'; $params[':email'] = $data['email']; }
        if (isset($data['role'])) { $fields[] = 'role = :role'; $params[':role'] = $data['role']; }

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

    public function verifyPassword(string $password, string $hash)
    {
        return password_verify($password, $hash);
    }

    public function authenticate(string $username, string $password)
    {
        $user = $this->getByUsername($username);
        if ($user && $this->verifyPassword($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
