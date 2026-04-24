<?php
namespace App\Models;

use App\Core\Database;

class User {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        return $this->db->selectOne("SELECT * FROM users WHERE id = :id", ['id' => $id]);
    }

    public function findByEmail(string $email): ?array {
        return $this->db->selectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    }

    public function findByUsername(string $username): ?array {
        return $this->db->selectOne("SELECT * FROM users WHERE username = :username", ['username' => $username]);
    }

    public function create(array $data): int {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('users', $data);
    }

    public function update(int $id, array $data): int {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        return $this->db->update('users', $data, 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): int {
        return $this->db->delete('users', 'id = :id', ['id' => $id]);
    }

    public function getAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        return $this->db->select(
            "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            ['limit' => $perPage, 'offset' => $offset]
        );
    }

    public function count(): int {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM users");
        return (int) $result['count'];
    }

    public function updateLastLogin(int $id): void {
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    }

    public function setActive(int $id, bool $active): int {
        return $this->db->update('users', ['is_active' => $active ? 1 : 0], 'id = :id', ['id' => $id]);
    }
}
