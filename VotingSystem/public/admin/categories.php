<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/CSRF.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/Category.php';
require_once __DIR__ . '/../../app/Models/Candidate.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Category;
use App\Models\Candidate;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('admin');

$user = Auth::user();
$categoryModel = new Category();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateRequest()) {
        flash('error', 'Invalid request. Please try again.');
        Response::redirect($_SERVER['REQUEST_URI']);
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $data = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $categoryModel->create($data);
        (new AuditLog())->log($user['id'], 'admin', 'add_category', "Added category: {$data['name']}");
        flash('success', 'Category added successfully.');
    } elseif ($action === 'update') {
        $id = (int) $_POST['id'];
        $data = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'display_order' => (int) ($_POST['display_order'] ?? 0)
        ];
        
        $categoryModel->update($id, $data);
        (new AuditLog())->log($user['id'], 'admin', 'update_category', "Updated category ID: {$id}");
        flash('success', 'Category updated successfully.');
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        if (!$categoryModel->hasCandidates($id)) {
            $categoryModel->delete($id);
            (new AuditLog())->log($user['id'], 'admin', 'delete_category', "Deleted category ID: {$id}");
            flash('success', 'Category deleted successfully.');
        } else {
            flash('error', 'Cannot delete category with candidates. Remove candidates first.');
        }
    }
    
    Response::redirect($_SERVER['REQUEST_URI']);
}

$categories = $categoryModel->getAll();
$allCategories = App\Core\Database::getInstance()->select("SELECT * FROM categories ORDER BY display_order ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - <?= htmlspecialchars(APP_NAME) ?></title>
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
            <a href="<?= APP_URL ?>/public/admin/categories.php" class="sidebar-link active"><i class="fas fa-tags"></i> Categories</a>
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
                    <h4 class="mb-1">Manage Categories</h4>
                    <p class="mb-0 text-muted">Create and manage election positions</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add Category
                </button>
            </div>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-custom alert-success-custom"><?= getFlash('success') ?></div>
            <?php endif; ?>
            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger alert-custom alert-danger-custom"><?= getFlash('error') ?></div>
            <?php endif; ?>
            
            <div class="card-custom">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-custom mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px">Order</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Candidates</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allCategories as $category): 
                                    $candidateCount = (new \App\Models\Candidate())->countByCategory($category['id']);
                                ?>
                                    <tr>
                                        <td><?= $category['display_order'] ?></td>
                                        <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($category['description'] ?? '-') ?></td>
                                        <td><span class="badge bg-secondary"><?= $candidateCount ?></span></td>
                                        <td>
                                            <span class="badge <?= $category['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary btn-action" onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (!$categoryModel->hasCandidates($category['id'])): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                    <?= CSRF::getField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-action">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary btn-action" disabled title="Has candidates">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?= CSRF::getField() ?>
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., President" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" class="form-control" name="display_order" value="0">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?= CSRF::getField() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" class="form-control" name="display_order" id="edit_display_order">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_display_order').value = category.display_order;
            
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }
    </script>
</body>
</html>
