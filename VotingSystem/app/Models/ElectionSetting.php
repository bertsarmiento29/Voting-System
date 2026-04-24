<?php
namespace App\Models;

use App\Core\Database;

class ElectionSetting {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function get(): array {
        $settings = $this->db->selectOne("SELECT * FROM election_settings LIMIT 1");
        if (!$settings) {
            $this->createDefault();
            return $this->get();
        }
        return $settings;
    }

    public function update(array $data): int {
        $settings = $this->get();
        return $this->db->update('election_settings', $data, 'id = :id', ['id' => $settings['id']]);
    }

    private function createDefault(): void {
        $this->db->insert('election_settings', [
            'election_name' => 'Student Council Election',
            'is_active' => 1,
            'allow_voting' => 0,
            'show_results' => 0
        ]);
    }

    public function isVotingOpen(): bool {
        $settings = $this->get();
        
        if (!$settings['is_active'] || !$settings['allow_voting']) {
            return false;
        }

        $now = time();
        
        if ($settings['start_date']) {
            $start = strtotime($settings['start_date']);
            if ($now < $start) {
                return false;
            }
        }

        if ($settings['end_date']) {
            $end = strtotime($settings['end_date']);
            if ($now > $end) {
                return false;
            }
        }

        return true;
    }

    public function canShowResults(): bool {
        $settings = $this->get();
        return (bool) $settings['show_results'];
    }

    public function enableVoting(): int {
        return $this->update(['allow_voting' => 1]);
    }

    public function disableVoting(): int {
        return $this->update(['allow_voting' => 0]);
    }

    public function enableResults(): int {
        return $this->update(['show_results' => 1]);
    }

    public function disableResults(): int {
        return $this->update(['show_results' => 0]);
    }

    public function toggleElection(): int {
        $settings = $this->get();
        return $this->update(['is_active' => $settings['is_active'] ? 0 : 1]);
    }
}
