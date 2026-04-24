<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/Models/Vote.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/ElectionSetting.php';

use App\Core\Session;
use App\Models\Vote;
use App\Models\Category;
use App\Models\ElectionSetting;

Session::start();

$settingsModel = new ElectionSetting();
$settings = $settingsModel->get();

if (!$settings['show_results']) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Results - ' . htmlspecialchars(APP_NAME) . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
        <link href="' . APP_URL . '/public/css/style.css" rel="stylesheet">
    </head>
    <body class="home-page">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="' . APP_URL . '/public/index.php">
                    <i class="fas fa-vote-yea me-2"></i>' . htmlspecialchars(APP_NAME) . '
                </a>
            </div>
        </nav>
        <div class="container py-5 text-center">
            <div class="vote-confirmation mx-auto" style="max-width: 500px;">
                <i class="fas fa-lock fa-5x text-warning mb-4"></i>
                <h3>Results Not Available</h3>
                <p class="text-muted">The election results are currently hidden. Please check back later.</p>
                <a href="' . APP_URL . '/public/index.php" class="btn btn-primary mt-3">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

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
    <title>Election Results - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/css/style.css" rel="stylesheet">
</head>
<body class="home-page">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= APP_URL ?>/public/index.php">
                <i class="fas fa-vote-yea me-2"></i><?= htmlspecialchars(APP_NAME) ?>
            </a>
            <div class="d-flex">
                <a href="<?= APP_URL ?>/public/voter/login.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-vote-yea me-1"></i>Vote
                </a>
                <a href="<?= APP_URL ?>/public/index.php" class="btn btn-light btn-sm">
                    <i class="fas fa-home me-1"></i>Home
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-white">
                <i class="fas fa-chart-bar me-3"></i>Election Results
            </h1>
            <p class="lead text-white opacity-75"><?= htmlspecialchars($settings['election_name']) ?></p>
            <div class="badge bg-light text-dark fs-6">
                <i class="fas fa-check-circle me-1"></i><?= number_format($totalVotes) ?> Total Votes Cast
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($categories as $category): 
                $categoryResults = $results[$category['id']] ?? ['candidates' => [], 'total_votes' => 0];
                $maxVotes = 0;
                $winner = null;
                foreach ($categoryResults['candidates'] as $candidate) {
                    if ($candidate['vote_count'] > $maxVotes) {
                        $maxVotes = $candidate['vote_count'];
                        $winner = $candidate;
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
                            <p class="text-muted text-center py-4">No results available.</p>
                        <?php else: ?>
                            <div class="chart-container mb-4">
                                <canvas id="chart-<?= $category['id'] ?>"></canvas>
                            </div>
                            
                            <div class="results-list">
                                <?php foreach ($categoryResults['candidates'] as $candidate): 
                                    $isWinner = $candidate['vote_count'] == $maxVotes && $maxVotes > 0;
                                ?>
                                    <div class="d-flex align-items-center mb-3 <?= $isWinner ? 'bg-light rounded p-2' : '' ?>">
                                        <?php if ($isWinner): ?>
                                            <div class="me-3">
                                                <i class="fas fa-trophy fa-2x text-warning"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong>
                                                    <?= htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) ?>
                                                    <?php if ($candidate['party_group']): ?>
                                                        <small class="text-muted">(<?= htmlspecialchars($candidate['party_group']) ?>)</small>
                                                    <?php endif; ?>
                                                </strong>
                                                <span>
                                                    <?= $candidate['vote_count'] ?> votes (<?= $candidate['percentage'] ?>%)
                                                    <?php if ($isWinner): ?>
                                                        <span class="winner-badge ms-2"><i class="fas fa-star me-1"></i>Leading</span>
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
        
        <div class="text-center mt-5">
            <a href="<?= APP_URL ?>/public/index.php" class="btn btn-light btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Back to Home
            </a>
        </div>
    </div>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const colors = [
            '#4a90d9', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1',
            '#fd7e14', '#20c997', '#e83e8c', '#6610f2', '#007bff', '#17a2b8'
        ];
        
        <?php foreach ($categories as $index => $category): 
            $categoryResults = $results[$category['id']] ?? ['candidates' => [], 'total_votes' => 0];
            if (empty($categoryResults['candidates'])) continue;
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
