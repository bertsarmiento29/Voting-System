<?php
namespace App\Models;

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Database;

class AuditLog {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function log(int|null $userId, string $userType, string $action, ?string $description = null): void {
        $data = [
            'user_id' => $userId,
            'user_type' => $userType,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('audit_logs', $data);
    }

    public function getAll(int $page = 1, int $perPage = 50): array {
        $offset = ($page - 1) * $perPage;
        return $this->db->select(
            "SELECT al.*, 
                    CASE 
                        WHEN al.user_type = 'admin' THEN u.full_name 
                        WHEN al.user_type = 'voter' THEN v.first_name 
                    END as user_name
             FROM audit_logs al
             LEFT JOIN users u ON al.user_type = 'admin' AND al.user_id = u.id
             LEFT JOIN voters v ON al.user_type = 'voter' AND al.user_id = v.id
             ORDER BY al.created_at DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $perPage, 'offset' => $offset]
        );
    }

    public function count(): int {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM audit_logs");
        return (int) $result['count'];
    }

    public function getByUser(int $userId, string $userType, int $limit = 50): array {
        return $this->db->select(
            "SELECT * FROM audit_logs WHERE user_id = :user_id AND user_type = :user_type ORDER BY created_at DESC LIMIT :limit",
            ['user_id' => $userId, 'user_type' => $userType, 'limit' => $limit]
        );
    }

    public function getByAction(string $action, int $limit = 100): array {
        return $this->db->select(
            "SELECT * FROM audit_logs WHERE action = :action ORDER BY created_at DESC LIMIT :limit",
            ['action' => $action, 'limit' => $limit]
        );
    }

    public function getRecent(int $limit = 10): array {
        return $this->db->select(
            "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT :limit",
            ['limit' => $limit]
        );
    }

    public function cleanOld(int $days = 90): int {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->db->delete('audit_logs', 'created_at < :cutoff', ['cutoff' => $cutoff]);
    }
}
