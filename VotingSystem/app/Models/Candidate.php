<?php
namespace App\Models;

use App\Core\Database;

class Candidate {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        return $this->db->selectOne("SELECT * FROM candidates WHERE id = :id", ['id' => $id]);
    }

    public function create(array $data): int {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('candidates', $data);
    }

    public function update(int $id, array $data): int {
        return $this->db->update('candidates', $data, 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): int {
        return $this->db->delete('candidates', 'id = :id', ['id' => $id]);
    }

    public function getAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        return $this->db->select(
            "SELECT c.*, cat.name as category_name 
             FROM candidates c 
             LEFT JOIN categories cat ON c.category_id = cat.id 
             ORDER BY cat.display_order, c.last_name 
             LIMIT :limit OFFSET :offset",
            ['limit' => $perPage, 'offset' => $offset]
        );
    }

    public function getAllWithDetails(): array {
        return $this->db->select(
            "SELECT c.*, cat.name as category_name 
             FROM candidates c 
             LEFT JOIN categories cat ON c.category_id = cat.id 
             WHERE c.is_active = 1 
             ORDER BY cat.display_order, c.last_name"
        );
    }

    public function getByCategory(int $categoryId): array {
        return $this->db->select(
            "SELECT * FROM candidates WHERE category_id = :category_id AND is_active = 1 ORDER BY last_name",
            ['category_id' => $categoryId]
        );
    }

    public function count(): int {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM candidates WHERE is_active = 1");
        return (int) $result['count'];
    }

    public function countByCategory(int $categoryId): int {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM candidates WHERE category_id = :category_id AND is_active = 1",
            ['category_id' => $categoryId]
        );
        return (int) $result['count'];
    }

    public function setActive(int $id, bool $active): int {
        return $this->db->update('candidates', ['is_active' => $active ? 1 : 0], 'id = :id', ['id' => $id]);
    }

    public function search(string $query): array {
        return $this->db->select(
            "SELECT c.*, cat.name as category_name 
             FROM candidates c 
             LEFT JOIN categories cat ON c.category_id = cat.id 
             WHERE (c.first_name LIKE :query OR c.last_name LIKE :query) 
             AND c.is_active = 1 
             ORDER BY cat.display_order, c.last_name",
            ['query' => "%{$query}%"]
        );
    }
}
