<?php
namespace App\Models;

use App\Core\Database;

class Category {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        return $this->db->selectOne("SELECT * FROM categories WHERE id = :id", ['id' => $id]);
    }

    public function create(array $data): int {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('categories', $data);
    }

    public function update(int $id, array $data): int {
        return $this->db->update('categories', $data, 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): int {
        return $this->db->delete('categories', 'id = :id', ['id' => $id]);
    }

    public function getAll(): array {
        return $this->db->select("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
    }

    public function getAllWithCandidates(): array {
        $categories = $this->getAll();
        $candidateModel = new Candidate();
        
        foreach ($categories as &$category) {
            $category['candidates'] = $candidateModel->getByCategory($category['id']);
        }
        
        return $categories;
    }

    public function count(): int {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM categories WHERE is_active = 1");
        return (int) $result['count'];
    }

    public function setActive(int $id, bool $active): int {
        return $this->db->update('categories', ['is_active' => $active ? 1 : 0], 'id = :id', ['id' => $id]);
    }

    public function reorder(array $order): void {
        foreach ($order as $position => $id) {
            $this->db->update('categories', ['display_order' => $position], 'id = :id', ['id' => (int) $id]);
        }
    }

    public function hasCandidates(int $id): bool {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM candidates WHERE category_id = :category_id",
            ['category_id' => $id]
        );
        return (int) $result['count'] > 0;
    }
}
