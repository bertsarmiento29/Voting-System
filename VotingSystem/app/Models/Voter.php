<?php
namespace App\Models;

use App\Core\Database;

class Voter {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        return $this->db->selectOne("SELECT * FROM voters WHERE id = :id", ['id' => $id]);
    }

    public function findByVoterId(string $voterId): ?array {
        return $this->db->selectOne("SELECT * FROM voters WHERE voter_id = :voter_id", ['voter_id' => $voterId]);
    }

    public function findByStudentId(string $studentId): ?array {
        return $this->db->selectOne("SELECT * FROM voters WHERE student_id = :student_id", ['student_id' => $studentId]);
    }

    public function findByEmail(string $email): ?array {
        return $this->db->selectOne("SELECT * FROM voters WHERE email = :email", ['email' => $email]);
    }

    public function create(array $data): int {
        if (!isset($data['password']) && isset($data['default_password'])) {
            $data['password'] = password_hash($data['default_password'], PASSWORD_BCRYPT, ['cost' => 12]);
            unset($data['default_password']);
        }
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('voters', $data);
    }

    public function update(int $id, array $data): int {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            unset($data['password']);
        }
        return $this->db->update('voters', $data, 'id = :id', ['id' => $id]);
    }

    public function delete(int $id): int {
        return $this->db->delete('voters', 'id = :id', ['id' => $id]);
    }

    public function getAll(int $page = 1, int $perPage = 20, string $search = ''): array {
        $offset = ($page - 1) * $perPage;
        $params = ['limit' => $perPage, 'offset' => $offset];
        
        $where = '';
        if ($search) {
            $where = "WHERE voter_id LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR email LIKE :search";
            $params['search'] = "%{$search}%";
        }
        
        return $this->db->select(
            "SELECT * FROM voters {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function count(string $search = ''): int {
        $params = [];
        $where = '';
        if ($search) {
            $where = "WHERE voter_id LIKE :search OR first_name LIKE :search OR last_name LIKE :search";
            $params['search'] = "%{$search}%";
        }
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM voters {$where}", $params);
        return (int) $result['count'];
    }

    public function countVoted(): int {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM voters WHERE has_voted = 1");
        return (int) $result['count'];
    }

    public function updateLastLogin(int $id): void {
        $this->db->update('voters', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    }

    public function markAsVoted(int $id): int {
        return $this->db->update('voters', ['has_voted' => 1], 'id = :id', ['id' => $id]);
    }

    public function setActive(int $id, bool $active): int {
        return $this->db->update('voters', ['is_active' => $active ? 1 : 0], 'id = :id', ['id' => $id]);
    }

    public function bulkCreate(array $voters): int {
        $count = 0;
        foreach ($voters as $voter) {
            if ($this->create($voter)) {
                $count++;
            }
        }
        return $count;
    }

    public function getActive(): array {
        return $this->db->select("SELECT * FROM voters WHERE is_active = 1 ORDER BY last_name, first_name");
    }
}
