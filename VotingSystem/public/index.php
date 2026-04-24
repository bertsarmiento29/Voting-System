<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';

App\Core\Session::start();

$db = App\Core\Database::getInstance();
$settings = $db->selectOne("SELECT * FROM election_settings LIMIT 1") ?? [
    'election_name' => 'Student Council Election',
    'is_active' => 0,
    'allow_voting' => 0,
    'show_results' => 0
];

$stats = [
    'voters' => $db->selectOne("SELECT COUNT(*) as count FROM voters WHERE is_active = 1")['count'] ?? 0,
    'candidates' => $db->selectOne("SELECT COUNT(*) as count FROM candidates WHERE is_active = 1")['count'] ?? 0,
    'votes_cast' => $db->selectOne("SELECT COUNT(*) as count FROM votes")['count'] ?? 0,
    'categories' => $db->selectOne("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")['count'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="home-page">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= APP_URL ?>/public/index.php">
                <i class="fas fa-vote-yea me-2"></i><?= htmlspecialchars(APP_NAME) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/voter/login.php">
                            <i class="fas fa-user me-1"></i> Voter Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/admin/login.php">
                            <i class="fas fa-user-shield me-1"></i> Admin Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        <i class="fas fa-vote-yea me-3"></i><?= htmlspecialchars($settings['election_name'] ?? 'Student Council Election') ?>
                    </h1>
                    <p class="lead text-white mb-4">
                        Exercise your right to vote securely and conveniently. 
                        Your voice matters in shaping our future.
                    </p>
                    <?php if ($settings['allow_voting']): ?>
                        <a href="<?= APP_URL ?>/public/voter/login.php" class="btn btn-light btn-lg me-2">
                            <i class="fas fa-check-circle me-2"></i>Cast Your Vote
                        </a>
                    <?php else: ?>
                        <button class="btn btn-light btn-lg me-2" disabled>
                            <i class="fas fa-clock me-2"></i>Voting Currently Closed
                        </button>
                    <?php endif; ?>
                    <?php if ($settings['show_results']): ?>
                        <a href="<?= APP_URL ?>/public/results.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-chart-bar me-2"></i>View Results
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="election-status-card">
                        <div class="status-indicator <?= $settings['is_active'] ? 'active' : 'inactive' ?>">
                            <i class="fas fa-<?= $settings['is_active'] ? 'check-circle' : 'times-circle' ?> fa-4x mb-3"></i>
                            <h3><?= $settings['is_active'] ? 'Election Active' : 'Election Inactive' ?></h3>
                        </div>
                        <div class="voting-status mt-4">
                            <span class="badge <?= $settings['allow_voting'] ? 'bg-success' : 'bg-danger' ?> fs-6">
                                <i class="fas fa-<?= $settings['allow_voting'] ? 'unlock' : 'lock' ?> me-1"></i>
                                Voting <?= $settings['allow_voting'] ? 'Open' : 'Closed' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="stats-section py-5 bg-white">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['voters']) ?></h3>
                            <p>Registered Voters</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['candidates']) ?></h3>
                            <p>Candidates</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['votes_cast']) ?></h3>
                            <p>Votes Cast</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= number_format($stats['categories']) ?></h3>
                            <p>Positions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Secure Online Voting System</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
