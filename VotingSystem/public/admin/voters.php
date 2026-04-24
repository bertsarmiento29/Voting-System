<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/CSRF.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/Voter.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Voter;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('admin');

$user = Auth::user();
$voterModel = new Voter();
$db = App\Core\Database::getInstance();

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;
$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateRequest()) {
        flash('error', 'Invalid request. Please try again.');
        Response::redirect($_SERVER['REQUEST_URI']);
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $data = [
            'voter_id' => trim($_POST['voter_id']),
            'student_id' => trim($_POST['student_id'] ?? ''),
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
            'year_level' => trim($_POST['year_level'] ?? ''),
            'default_password' => $_POST['password'] ?? 'password123',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if ($voterModel->findByVoterId($data['voter_id'])) {
            flash('error', 'Voter ID already exists.');
        } elseif ($voterModel->findByEmail($data['email'])) {
            flash('error', 'Email already exists.');
        } else {
            $voterModel->create($data);
            (new AuditLog())->log($user['id'], 'admin', 'add_voter', "Added voter: {$data['first_name']} {$data['last_name']}");
            flash('success', 'Voter added successfully.');
        }
    } elseif ($action === 'update') {
        $id = (int) $_POST['id'];
        $voter = $voterModel->findById($id);
        
        if ($voter) {
            $data = [
                'voter_id' => trim($_POST['voter_id']),
                'student_id' => trim($_POST['student_id'] ?? ''),
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'email' => trim($_POST['email'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'year_level' => trim($_POST['year_level'] ?? '')
            ];
            
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            $voterModel->update($id, $data);
            (new AuditLog())->log($user['id'], 'admin', 'update_voter', "Updated voter ID: {$id}");
            flash('success', 'Voter updated successfully.');
        }
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        $voterModel->delete($id);
        (new AuditLog())->log($user['id'], 'admin', 'delete_voter', "Deleted voter ID: {$id}");
        flash('success', 'Voter deleted successfully.');
    } elseif ($action === 'toggle_status') {
        $id = (int) $_POST['id'];
        $voter = $voterModel->findById($id);
        if ($voter) {
            $voterModel->setActive($id, !$voter['is_active']);
            flash('success', 'Voter status updated.');
        }
    }
    
    Response::redirect($_SERVER['REQUEST_URI']);
}

$voters = $voterModel->getAll($page, $perPage, $search);
$totalVoters = $voterModel->count($search);
$totalPages = ceil($totalVoters / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters - <?= htmlspecialchars(APP_NAME) ?></title>
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
            <a href="<?= APP_URL ?>/public/admin/voters.php" class="sidebar-link active"><i class="fas fa-users"></i> Voters</a>
            <a href="<?= APP_URL ?>/public/admin/candidates.php" class="sidebar-link"><i class="fas fa-user-tie"></i> Candidates</a>
            <a href="<?= APP_URL ?>/public/admin/categories.php" class="sidebar-link"><i class="fas fa-tags"></i> Categories</a>
            <a href="<?= APP_URL ?>/public/admin/election-settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= APP_URL ?>/public/admin/results.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> Results</a>
            <a href="<?= APP_URL ?>/public/admin/audit-logs.php" class="sidebar-link"><i class="fas fa-history"></i> Audit Logs</a>
            <div class="mt-auto px-3 py-3 border-top border-secondary">
                <a href="<?= APP_URL ?>/public/admin/logout.php" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
        
        <main class="admin-content flex-grow-1">
            <div class="admin-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Manage Voters</h4>
                    <p class="mb-0 text-muted">Add, edit, and manage voter accounts</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVoterModal">
                    <i class="fas fa-plus me-2"></i>Add Voter
                </button>
            </div>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-custom alert-success-custom"><?= getFlash('success') ?></div>
            <?php endif; ?>
            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger alert-custom alert-danger-custom"><?= getFlash('error') ?></div>
            <?php endif; ?>
            
            <div class="card-custom mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search voters..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card-custom">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Voter ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Voted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($voters as $voter): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($voter['voter_id']) ?></strong></td>
                                        <td><?= htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']) ?></td>
                                        <td><?= htmlspecialchars($voter['email'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($voter['department'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge <?= $voter['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= $voter['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $voter['has_voted'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $voter['has_voted'] ? 'Yes' : 'No' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary btn-action" onclick="editVoter(<?= htmlspecialchars(json_encode($voter)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                <?= CSRF::getField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $voter['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-action">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
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
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
    
    <div class="modal fade" id="addVoterModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?= CSRF::getField() ?>
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Voter ID *</label>
                                <input type="text" class="form-control" name="voter_id" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" name="student_id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Default: password123">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" name="department">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Year Level</label>
                                <input type="text" class="form-control" name="year_level">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active Account</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Voter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editVoterModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?= CSRF::getField() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Voter ID *</label>
                                <input type="text" class="form-control" name="voter_id" id="edit_voter_id" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" name="student_id" id="edit_student_id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password (leave blank)</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter new password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" name="department" id="edit_department">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Year Level</label>
                                <input type="text" class="form-control" name="year_level" id="edit_year_level">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Voter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editVoter(voter) {
            document.getElementById('edit_id').value = voter.id;
            document.getElementById('edit_voter_id').value = voter.voter_id;
            document.getElementById('edit_student_id').value = voter.student_id || '';
            document.getElementById('edit_first_name').value = voter.first_name;
            document.getElementById('edit_last_name').value = voter.last_name;
            document.getElementById('edit_email').value = voter.email || '';
            document.getElementById('edit_department').value = voter.department || '';
            document.getElementById('edit_year_level').value = voter.year_level || '';
            
            new bootstrap.Modal(document.getElementById('editVoterModal')).show();
        }
    </script>
</body>
</html>
