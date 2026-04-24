<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/Voter.php';
require_once __DIR__ . '/../../app/Models/Candidate.php';
require_once __DIR__ . '/../../app/Models/Category.php';
require_once __DIR__ . '/../../app/Models/Vote.php';
require_once __DIR__ . '/../../app/Models/ElectionSetting.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\Voter;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Vote;
use App\Models\ElectionSetting;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('admin');

$user = Auth::user();
$db = App\Core\Database::getInstance();

$stats = [
    'total_voters' => (new Voter())->count(),
    'active_voters' => $db->selectOne("SELECT COUNT(*) as count FROM voters WHERE is_active = 1")['count'],
    'voters_voted' => (new Voter())->countVoted(),
    'total_candidates' => (new Candidate())->count(),
    'total_categories' => (new Category())->count(),
    'total_votes' => (new Vote())->countTotal(),
    'recent_votes' => $db->select("SELECT v.*, vot.first_name, vot.last_name, c.first_name as c_first, c.last_name as c_last, cat.name as category 
                                   FROM votes v 
                                   JOIN voters vot ON v.voter_id = vot.id 
                                   JOIN candidates c ON v.candidate_id = c.id 
                                   JOIN categories cat ON v.category_id = cat.id 
                                   ORDER BY v.vote_timestamp DESC LIMIT 5"),
    'audit_logs' => (new AuditLog())->getRecent(10)
];

$settings = (new ElectionSetting())->get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <nav class="admin-sidebar">
            <div class="text-center mb-4 px-3">
                <i class="fas fa-vote-yea fa-2x mb-2"></i>
                <h5 class="mb-0"><?= htmlspecialchars(APP_NAME) ?></h5>
                <small class="opacity-75">Admin Panel</small>
            </div>
            
            <div class="px-3 mb-4">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-2">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <small class="d-block opacity-75">Logged in as</small>
                        <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                    </div>
                </div>
            </div>
            
            <a href="<?= APP_URL ?>/public/admin/dashboard.php" class="sidebar-link active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="<?= APP_URL ?>/public/admin/voters.php" class="sidebar-link">
                <i class="fas fa-users"></i> Voters
            </a>
            <a href="<?= APP_URL ?>/public/admin/candidates.php" class="sidebar-link">
                <i class="fas fa-user-tie"></i> Candidates
            </a>
            <a href="<?= APP_URL ?>/public/admin/categories.php" class="sidebar-link">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="<?= APP_URL ?>/public/admin/election-settings.php" class="sidebar-link">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="<?= APP_URL ?>/public/admin/results.php" class="sidebar-link">
                <i class="fas fa-chart-bar"></i> Results
            </a>
            <a href="<?= APP_URL ?>/public/admin/audit-logs.php" class="sidebar-link">
                <i class="fas fa-history"></i> Audit Logs
            </a>
            
            <div class="mt-auto px-3 py-3 border-top border-secondary">
                <a href="<?= APP_URL ?>/public/admin/logout.php" class="sidebar-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
        
        <main class="admin-content flex-grow-1">
            <div class="admin-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Dashboard</h4>
                    <p class="mb-0 text-muted">Welcome back, <?= htmlspecialchars($user['full_name']) ?></p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($settings['allow_voting']): ?>
                        <span class="badge bg-success fs-6"><i class="fas fa-unlock me-1"></i>Voting Open</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6"><i class="fas fa-lock me-1"></i>Voting Closed</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-custom alert-success-custom">
                    <i class="fas fa-check-circle me-2"></i><?= getFlash('success') ?>
                </div>
            <?php endif; ?>
            
            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger alert-custom alert-danger-custom">
                    <i class="fas fa-exclamation-circle me-2"></i><?= getFlash('error') ?>
                </div>
            <?php endif; ?>
            
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total Voters</p>
                                    <h3 class="mb-0"><?= number_format($stats['total_voters']) ?></h3>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Votes Cast</p>
                                    <h3 class="mb-0"><?= number_format($stats['total_votes']) ?></h3>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-check-double"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Candidates</p>
                                    <h3 class="mb-0"><?= number_format($stats['total_candidates']) ?></h3>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Positions</p>
                                    <h3 class="mb-0"><?= number_format($stats['total_categories']) ?></h3>
                                </div>
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-tags"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-custom">
                        <div class="card-header">
                            <i class="fas fa-chart-line me-2"></i>Voting Progress
                        </div>
                        <div class="card-body">
                            <?php
                            $turnout = $stats['active_voters'] > 0 ? round(($stats['voters_voted'] / $stats['active_voters']) * 100, 1) : 0;
                            ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Voter Turnout</span>
                                    <span><?= $stats['voters_voted'] ?> / <?= $stats['active_voters'] ?> (<?= $turnout ?>%)</span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="progress-bar-fill" style="width: <?= $turnout ?>%"></div>
                                </div>
                            </div>
                            
                            <h5 class="mb-3">Recent Votes</h5>
                            <?php if (empty($stats['recent_votes'])): ?>
                                <p class="text-muted text-center py-4">No votes recorded yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Voter</th>
                                                <th>Candidate</th>
                                                <th>Position</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['recent_votes'] as $vote): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($vote['first_name'] . ' ' . $vote['last_name']) ?></td>
                                                    <td><?= htmlspecialchars($vote['c_first'] . ' ' . $vote['c_last']) ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($vote['category']) ?></span></td>
                                                    <td>
                                                        <div><small><?= timeAgo($vote['vote_timestamp']) ?></small></div>
                                                        <small class="text-muted"><?= formatDateTime($vote['vote_timestamp']) ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-header">
                            <i class="fas fa-cog me-2"></i>Election Controls
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Election Status</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?= $settings['is_active'] ? 'checked' : '' ?> onchange="toggleSetting('is_active', this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Allow Voting</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?= $settings['allow_voting'] ? 'checked' : '' ?> onchange="toggleSetting('allow_voting', this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Show Results</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?= $settings['show_results'] ? 'checked' : '' ?> onchange="toggleSetting('show_results', this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-custom">
                        <div class="card-header">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($stats['audit_logs'])): ?>
                                <p class="text-muted text-center py-4">No recent activity.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($stats['audit_logs'] as $log): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <small class="me-2"><?= htmlspecialchars($log['action']) ?></small>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-clock me-1"></i><?= formatDateTime($log['created_at']) ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSetting(setting, value) {
            fetch('<?= APP_URL ?>/public/admin/api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?= $_SESSION['csrf_token']['token'] ?? '' ?>'
                },
                body: JSON.stringify({ setting, value })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update setting');
                }
            });
        }
    </script>
</body>
</html>
