<?php

require_once __DIR__ . '/DB.php';

abstract class BaseModel
{
    protected \PDO $pdo;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->pdo = DB::getPDO();
    }

    protected function fetchAll(PDOStatement $stmt): array
    {
        $stmt->execute();
        return $stmt->fetchAll();
    }

    protected function fetchOne(PDOStatement $stmt): array|false
    {
        $stmt->execute();
        return $stmt->fetch();
    }
}
