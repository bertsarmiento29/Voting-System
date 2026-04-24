<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/CSRF.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/ElectionSetting.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\ElectionSetting;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('admin');

$user = Auth::user();
$settingsModel = new ElectionSetting();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateRequest()) {
        flash('error', 'Invalid request.');
        Response::redirect($_SERVER['REQUEST_URI']);
    }
    
    $data = [
        'election_name' => trim($_POST['election_name']),
        'description' => trim($_POST['description'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'allow_voting' => isset($_POST['allow_voting']) ? 1 : 0,
        'show_results' => isset($_POST['show_results']) ? 1 : 0
    ];
    
    if (!empty($_POST['start_date'])) {
        $data['start_date'] = $_POST['start_date'];
    }
    if (!empty($_POST['end_date'])) {
        $data['end_date'] = $_POST['end_date'];
    }
    
    $settingsModel->update($data);
    (new AuditLog())->log($user['id'], 'admin', 'update_settings', 'Updated election settings');
    flash('success', 'Settings updated successfully.');
    Response::redirect($_SERVER['REQUEST_URI']);
}

$settings = $settingsModel->get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Settings - <?= htmlspecialchars(APP_NAME) ?></title>
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
            <a href="<?= APP_URL ?>/public/admin/election-settings.php" class="sidebar-link active"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= APP_URL ?>/public/admin/results.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> Results</a>
            <a href="<?= APP_URL ?>/public/admin/audit-logs.php" class="sidebar-link"><i class="fas fa-history"></i> Audit Logs</a>
            <div class="mt-auto px-3 py-3 border-top border-secondary">
                <a href="<?= APP_URL ?>/public/admin/logout.php" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
        
        <main class="admin-content flex-grow-1">
            <div class="admin-header">
                <h4 class="mb-1">Election Settings</h4>
                <p class="mb-0 text-muted">Configure election parameters and controls</p>
            </div>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-custom alert-success-custom"><?= getFlash('success') ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom">
                        <div class="card-header">
                            <i class="fas fa-cog me-2"></i>General Settings
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= CSRF::getField() ?>
                                <div class="mb-3">
                                    <label class="form-label">Election Name</label>
                                    <input type="text" class="form-control" name="election_name" value="<?= htmlspecialchars($settings['election_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($settings['description'] ?? '') ?></textarea>
                                </div>
                                
                                <hr>
                                <h5 class="mb-3">Schedule</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Date & Time</label>
                                        <input type="datetime-local" class="form-control" name="start_date" value="<?= $settings['start_date'] ? date('Y-m-d\TH:i', strtotime($settings['start_date'])) : '' ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Date & Time</label>
                                        <input type="datetime-local" class="form-control" name="end_date" value="<?= $settings['end_date'] ? date('Y-m-d\TH:i', strtotime($settings['end_date'])) : '' ?>">
                                    </div>
                                </div>
                                
                                <hr>
                                <h5 class="mb-3">Controls</h5>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?= $settings['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            <strong>Election Active</strong> - Enable to start the election
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="allow_voting" name="allow_voting" <?= $settings['allow_voting'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allow_voting">
                                            <strong>Allow Voting</strong> - Enable to let voters cast votes
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="show_results" name="show_results" <?= $settings['show_results'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_results">
                                            <strong>Show Results</strong> - Display results to public
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-header">
                            <i class="fas fa-info-circle me-2"></i>Current Status
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Election Status:</span>
                                <span class="badge <?= $settings['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $settings['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Voting:</span>
                                <span class="badge <?= $settings['allow_voting'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $settings['allow_voting'] ? 'Open' : 'Closed' ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Results:</span>
                                <span class="badge <?= $settings['show_results'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $settings['show_results'] ? 'Public' : 'Hidden' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-custom">
                        <div class="card-header">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Quickly toggle election status:</p>
                            <form method="POST" action="api/toggle-voting.php">
                                <?= CSRF::getField() ?>
                                <?php if ($settings['allow_voting']): ?>
                                    <button type="submit" class="btn btn-danger w-100 mb-2">
                                        <i class="fas fa-stop me-2"></i>Stop Voting
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-success w-100 mb-2">
                                        <i class="fas fa-play me-2"></i>Start Voting
                                    </button>
                                <?php endif; ?>
                            </form>
                            <form method="POST" action="api/toggle-results.php">
                                <?= CSRF::getField() ?>
                                <?php if ($settings['show_results']): ?>
                                    <button type="submit" class="btn btn-secondary w-100">
                                        <i class="fas fa-eye-slash me-2"></i>Hide Results
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-eye me-2"></i>Show Results
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
