<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../app/Models/Vote.php';
require_once __DIR__ . '/../../app/Models/Category.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\Vote;
use App\Models\Category;

Session::start();
Auth::requireAuth('admin');

$voteModel = new Vote();
$categoryModel = new Category();

$results = $voteModel->getResultsWithPercentage();
$categories = $categoryModel->getAll();
$totalVotes = $voteModel->countTotal();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js">
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
            <a href="<?= APP_URL ?>/public/admin/dashboard.php" class="sidebar-link"><i class="fas fa-home"></i> Dashboard</a>
            <a href="<?= APP_URL ?>/public/admin/voters.php" class="sidebar-link"><i class="fas fa-users"></i> Voters</a>
            <a href="<?= APP_URL ?>/public/admin/candidates.php" class="sidebar-link"><i class="fas fa-user-tie"></i> Candidates</a>
            <a href="<?= APP_URL ?>/public/admin/categories.php" class="sidebar-link"><i class="fas fa-tags"></i> Categories</a>
            <a href="<?= APP_URL ?>/public/admin/election-settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= APP_URL ?>/public/admin/results.php" class="sidebar-link active"><i class="fas fa-chart-bar"></i> Results</a>
            <a href="<?= APP_URL ?>/public/admin/audit-logs.php" class="sidebar-link"><i class="fas fa-history"></i> Audit Logs</a>
            <div class="mt-auto px-3 py-3 border-top border-secondary">
                <a href="<?= APP_URL ?>/public/admin/logout.php" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
        
        <main class="admin-content flex-grow-1">
            <div class="admin-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Election Results</h4>
                    <p class="mb-0 text-muted">Real-time vote counting and analytics</p>
                </div>
                <div class="badge bg-primary fs-6">
                    <i class="fas fa-check-circle me-1"></i><?= number_format($totalVotes) ?> Total Votes
                </div>
            </div>
            
            <div class="row mb-4">
                <?php foreach ($categories as $category): 
                    $categoryResults = $results[$category['id']] ?? ['candidates' => [], 'total_votes' => 0];
                    $maxVotes = 0;
                    foreach ($categoryResults['candidates'] as $candidate) {
                        if ($candidate['vote_count'] > $maxVotes) {
                            $maxVotes = $candidate['vote_count'];
                        }
                    }
                ?>
                    <div class="col-lg-6 mb-4">
                        <div class="result-card">
                            <h5 class="result-header">
                                <i class="fas fa-tag me-2"></i><?= htmlspecialchars($category['name']) ?>
                                <span class="badge bg-secondary ms-2"><?= $categoryResults['total_votes'] ?> votes</span>
                            </h5>
                            
                            <?php if (empty($categoryResults['candidates'])): ?>
                                <p class="text-muted text-center py-4">No candidates for this position.</p>
                            <?php else: ?>
                                <div class="chart-container mb-3">
                                    <canvas id="chart-<?= $category['id'] ?>"></canvas>
                                </div>
                                
                                <div class="candidate-results">
                                    <?php foreach ($categoryResults['candidates'] as $candidate): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <strong><?= htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) ?></strong>
                                                    <span>
                                                        <?= $candidate['vote_count'] ?> votes (<?= $candidate['percentage'] ?>%)
                                                        <?php if ($candidate['vote_count'] == $maxVotes && $maxVotes > 0): ?>
                                                            <span class="winner-badge"><i class="fas fa-trophy me-1"></i>Leading</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <div class="progress-bar-custom">
                                                    <div class="progress-bar-fill" style="width: <?= $candidate['percentage'] ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const colors = [
            '#4a90d9', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1',
            '#fd7e14', '#20c997', '#e83e8c', '#6610f2', '#007bff', '#17a2b8'
        ];
        
        <?php foreach ($categories as $index => $category): 
            $categoryResults = $results[$category['id']] ?? ['candidates' => [], 'total_votes' => 0];
        ?>
            const ctx<?= $category['id'] ?> = document.getElementById('chart-<?= $category['id'] ?>').getContext('2d');
            new Chart(ctx<?= $category['id'] ?>, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_map(fn($c) => $c['first_name'] . ' ' . substr($c['last_name'], 0, 1) . '.', $categoryResults['candidates'])) ?>,
                    datasets: [{
                        label: 'Votes',
                        data: <?= json_encode(array_column($categoryResults['candidates'], 'vote_count')) ?>,
                        backgroundColor: colors.slice(0, <?= count($categoryResults['candidates']) ?>),
                        borderWidth: 0,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        <?php endforeach; ?>
    </script>
</body>
</html>
