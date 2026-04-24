<?php
namespace App\Models;

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Database;

class Vote {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function cast(int $voterId, int $candidateId, int $categoryId): bool {
        $existingVote = $this->db->selectOne(
            "SELECT id FROM votes WHERE voter_id = :voter_id AND category_id = :category_id",
            ['voter_id' => $voterId, 'category_id' => $categoryId]
        );

        if ($existingVote) {
            return false;
        }

        $data = [
            'voter_id' => $voterId,
            'candidate_id' => $candidateId,
            'category_id' => $categoryId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'vote_timestamp' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('votes', $data);
        return true;
    }

    public function hasVoted(int $voterId, int $categoryId): bool {
        $result = $this->db->selectOne(
            "SELECT id FROM votes WHERE voter_id = :voter_id AND category_id = :category_id",
            ['voter_id' => $voterId, 'category_id' => $categoryId]
        );
        return $result !== null;
    }

    public function getVoterVotes(int $voterId): array {
        return $this->db->select(
            "SELECT v.*, c.first_name, c.last_name, cat.name as category_name 
             FROM votes v 
             LEFT JOIN candidates c ON v.candidate_id = c.id 
             LEFT JOIN categories cat ON v.category_id = cat.id 
             WHERE v.voter_id = :voter_id 
             ORDER BY v.vote_timestamp DESC",
            ['voter_id' => $voterId]
        );
    }

    public function countByCandidate(int $candidateId): int {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM votes WHERE candidate_id = :candidate_id",
            ['candidate_id' => $candidateId]
        );
        return (int) $result['count'];
    }

    public function countByCategory(int $categoryId): int {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM votes WHERE category_id = :category_id",
            ['category_id' => $categoryId]
        );
        return (int) $result['count'];
    }

    public function countTotal(): int {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM votes");
        return (int) $result['count'];
    }

    public function getResultsByCategory(int $categoryId): array {
        return $this->db->select(
            "SELECT c.id, c.first_name, c.last_name, c.party_group, c.photo,
                    COUNT(v.id) as vote_count
             FROM candidates c
             LEFT JOIN votes v ON c.id = v.candidate_id
             WHERE c.category_id = :category_id AND c.is_active = 1
             GROUP BY c.id
             ORDER BY vote_count DESC, c.last_name ASC",
            ['category_id' => $categoryId]
        );
    }

    public function getAllResults(): array {
        $categories = (new Category())->getAll();
        $results = [];

        foreach ($categories as $category) {
            $categoryId = $category['id'];
            $categoryResults = $this->getResultsByCategory($categoryId);
            $totalVotes = array_sum(array_column($categoryResults, 'vote_count'));
            
            $results[$categoryId] = [
                'category' => $category,
                'candidates' => $categoryResults,
                'total_votes' => $totalVotes
            ];
        }

        return $results;
    }

    public function getResultsWithPercentage(): array {
        $results = $this->getAllResults();
        
        foreach ($results as &$result) {
            foreach ($result['candidates'] as &$candidate) {
                $candidate['percentage'] = $result['total_votes'] > 0 
                    ? round(($candidate['vote_count'] / $result['total_votes']) * 100, 1)
                    : 0;
            }
        }
        
        return $results;
    }

    public function getWinner(int $categoryId): ?array {
        $winner = $this->db->selectOne(
            "SELECT c.*, COUNT(v.id) as vote_count
             FROM candidates c
             LEFT JOIN votes v ON c.id = v.candidate_id
             WHERE c.category_id = :category_id AND c.is_active = 1
             GROUP BY c.id
             ORDER BY vote_count DESC
             LIMIT 1",
            ['category_id' => $categoryId]
        );
        return $winner ?: null;
    }

    public function removeVote(int $voterId, int $categoryId): bool {
        $affected = $this->db->delete(
            "voter_id = :voter_id AND category_id = :category_id",
            ['voter_id' => $voterId, 'category_id' => $categoryId]
        );
        return $affected > 0;
    }

    public function clearAll(): int {
        return $this->db->delete('votes', '1=1');
    }
}
