<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        $this->db->query(
            "INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())",
            [
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
                'role' => $role
            ]
        );

        return (int) $this->db->lastInsertId();
    }
    
    public function emailExists(string $email): bool
    {
        $result = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
        return $result !== null;
    }
}
