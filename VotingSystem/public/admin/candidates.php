<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/CSRF.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/Candidate.php';
require_once __DIR__ . '/../../app/Models/Category.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('admin');

$user = Auth::user();
$candidateModel = new Candidate();
$categoryModel = new Category();
$db = App\Core\Database::getInstance();

$categories = $categoryModel->getAll();
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 12;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateRequest()) {
        flash('error', 'Invalid request. Please try again.');
        Response::redirect($_SERVER['REQUEST_URI']);
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = uploadFile($_FILES['photo'], 'uploads/candidates/');
        }
        
        $data = [
            'category_id' => (int) $_POST['category_id'],
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'party_group' => trim($_POST['party_group'] ?? ''),
            'bio' => trim($_POST['bio'] ?? ''),
            'vision' => trim($_POST['vision'] ?? ''),
            'mission' => trim($_POST['mission'] ?? ''),
            'photo' => $photoPath,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $candidateModel->create($data);
        (new AuditLog())->log($user['id'], 'admin', 'add_candidate', "Added candidate: {$data['first_name']} {$data['last_name']}");
        flash('success', 'Candidate added successfully.');
    } elseif ($action === 'update') {
        $id = (int) $_POST['id'];
        $candidate = $candidateModel->findById($id);
        
        if ($candidate) {
            $data = [
                'category_id' => (int) $_POST['category_id'],
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'party_group' => trim($_POST['party_group'] ?? ''),
                'bio' => trim($_POST['bio'] ?? ''),
                'vision' => trim($_POST['vision'] ?? ''),
                'mission' => trim($_POST['mission'] ?? '')
            ];
            
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $data['photo'] = uploadFile($_FILES['photo'], 'uploads/candidates/');
            }
            
            $candidateModel->update($id, $data);
            (new AuditLog())->log($user['id'], 'admin', 'update_candidate', "Updated candidate ID: {$id}");
            flash('success', 'Candidate updated successfully.');
        }
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        $candidateModel->delete($id);
        (new AuditLog())->log($user['id'], 'admin', 'delete_candidate', "Deleted candidate ID: {$id}");
        flash('success', 'Candidate deleted successfully.');
    }
    
    Response::redirect($_SERVER['REQUEST_URI']);
}

$candidates = $candidateModel->getAll($page, $perPage);
$totalCandidates = $candidateModel->count();
$totalPages = ceil($totalCandidates / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - <?= htmlspecialchars(APP_NAME) ?></title>
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
            <a href="<?= APP_URL ?>/public/admin/candidates.php" class="sidebar-link active"><i class="fas fa-user-tie"></i> Candidates</a>
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
                    <h4 class="mb-1">Manage Candidates</h4>
                    <p class="mb-0 text-muted">Add and manage election candidates</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                    <i class="fas fa-plus me-2"></i>Add Candidate
                </button>
            </div>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-custom alert-success-custom"><?= getFlash('success') ?></div>
            <?php endif; ?>
            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger alert-custom alert-danger-custom"><?= getFlash('error') ?></div>
            <?php endif; ?>
            
            <div class="row g-4">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="candidate-card">
                            <?php if (!empty($candidate['photo'])): ?>
                                <img src="<?= APP_URL ?>/public/<?= htmlspecialchars($candidate['photo']) ?>" class="candidate-photo" alt="<?= htmlspecialchars($candidate['first_name']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="candidate-photo d-flex align-items-center justify-content-center bg-light" style="display:none;">
                                    <i class="fas fa-user fa-4x text-muted"></i>
                                </div>
                            <?php else: ?>
                                <div class="candidate-photo d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-user fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="candidate-info">
                                <h5 class="candidate-name"><?= htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) ?></h5>
                                <p class="candidate-party mb-1"><?= htmlspecialchars($candidate['category_name']) ?></p>
                                <?php if ($candidate['party_group']): ?>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars($candidate['party_group']) ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-grow-1" onclick="editCandidate(<?= htmlspecialchars(json_encode($candidate)) ?>)">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        <?= CSRF::getField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $candidate['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
    
    <div class="modal fade" id="addCandidateModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?= CSRF::getField() ?>
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Position/Category *</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Photo</label>
                                <input type="file" class="form-control" name="photo" accept="image/*">
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
                                <label class="form-label">Party/Group</label>
                                <input type="text" class="form-control" name="party_group" placeholder="e.g., Unity Party">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bio</label>
                                <textarea class="form-control" name="bio" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vision</label>
                                <textarea class="form-control" name="vision" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mission</label>
                                <textarea class="form-control" name="mission" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Candidate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editCandidateModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?= CSRF::getField() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Position/Category *</label>
                                <select class="form-select" name="category_id" id="edit_category_id" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Photo</label>
                                <input type="file" class="form-control" name="photo" accept="image/*">
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
                                <label class="form-label">Party/Group</label>
                                <input type="text" class="form-control" name="party_group" id="edit_party_group">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bio</label>
                                <textarea class="form-control" name="bio" id="edit_bio" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vision</label>
                                <textarea class="form-control" name="vision" id="edit_vision" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mission</label>
                                <textarea class="form-control" name="mission" id="edit_mission" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Candidate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCandidate(candidate) {
            document.getElementById('edit_id').value = candidate.id;
            document.getElementById('edit_category_id').value = candidate.category_id;
            document.getElementById('edit_first_name').value = candidate.first_name;
            document.getElementById('edit_last_name').value = candidate.last_name;
            document.getElementById('edit_party_group').value = candidate.party_group || '';
            document.getElementById('edit_bio').value = candidate.bio || '';
            document.getElementById('edit_vision').value = candidate.vision || '';
            document.getElementById('edit_mission').value = candidate.mission || '';
            
            new bootstrap.Modal(document.getElementById('editCandidateModal')).show();
        }
    </script>
</body>
</html>
