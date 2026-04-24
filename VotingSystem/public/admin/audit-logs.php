<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('admin');

$auditModel = new AuditLog();
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 30;

$logs = $auditModel->getAll($page, $perPage);
$totalLogs = $auditModel->count();
$totalPages = ceil($totalLogs / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - <?= htmlspecialchars(APP_NAME) ?></title>
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
            <a href="<?= APP_URL ?>/public/admin/dashboard.php" class="sidebar-link"><i class="fas fa-home"></i> Dashboard</a>
            <a href="<?= APP_URL ?>/public/admin/voters.php" class="sidebar-link"><i class="fas fa-users"></i> Voters</a>
            <a href="<?= APP_URL ?>/public/admin/candidates.php" class="sidebar-link"><i class="fas fa-user-tie"></i> Candidates</a>
            <a href="<?= APP_URL ?>/public/admin/categories.php" class="sidebar-link"><i class="fas fa-tags"></i> Categories</a>
            <a href="<?= APP_URL ?>/public/admin/election-settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= APP_URL ?>/public/admin/results.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> Results</a>
            <a href="<?= APP_URL ?>/public/admin/audit-logs.php" class="sidebar-link active"><i class="fas fa-history"></i> Audit Logs</a>
            <div class="mt-auto px-3 py-3 border-top border-secondary">
                <a href="<?= APP_URL ?>/public/admin/logout.php" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
        
        <main class="admin-content flex-grow-1">
            <div class="admin-header">
                <h4 class="mb-1">Audit Logs</h4>
                <p class="mb-0 text-muted">Track all system activities and user actions</p>
            </div>
            
            <div class="card-custom">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Date/Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $log['user_type'] === 'admin' ? 'bg-primary' : 'bg-info' ?>">
                                                <?= ucfirst($log['user_type']) ?>
                                            </span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($log['action']) ?></strong></td>
                                        <td><?= htmlspecialchars($log['description'] ?? '-') ?></td>
                                        <td><small><?= htmlspecialchars($log['ip_address'] ?? '-') ?></small></td>
                                        <td><?= formatDateTime($log['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
