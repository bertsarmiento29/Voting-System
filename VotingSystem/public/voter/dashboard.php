<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/CSRF.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Helpers.php';
require_once __DIR__ . '/../../app/Models/Voter.php';
require_once __DIR__ . '/../../app/Models/Category.php';
require_once __DIR__ . '/../../app/Models/Candidate.php';
require_once __DIR__ . '/../../app/Models/Vote.php';
require_once __DIR__ . '/../../app/Models/ElectionSetting.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Response;
use App\Models\Voter;
use App\Models\Category;
use App\Models\Candidate;
use App\Models\Vote;
use App\Models\ElectionSetting;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('voter');

$user = Auth::user();
$voterModel = new Voter();
$voter = $voterModel->findById($user['id']);

$settingsModel = new ElectionSetting();
$settings = $settingsModel->get();
$isVotingOpen = $settingsModel->isVotingOpen();

$categoryModel = new Category();
$candidateModel = new Candidate();
$voteModel = new Vote();

$categories = $categoryModel->getAllWithCandidates();
$voterVotes = $voteModel->getVoterVotes($user['id']);
$votedCategories = array_column($voterVotes, 'category_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isVotingOpen) {
    if (!CSRF::validateRequest()) {
        flash('error', 'Invalid request.');
        Response::redirect($_SERVER['REQUEST_URI']);
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit_all_votes') {
        $votes = $_POST['votes'] ?? [];
        
        if (empty($votes)) {
            flash('error', 'Please select candidates before submitting.');
        } else {
            $db = \App\Core\Database::getInstance()->getConnection();
            try {
                $db->beginTransaction();
                
                foreach ($votes as $categoryId => $candidateId) {
                    if (!empty($candidateId)) {
                        $voteModel->cast($user['id'], (int)$candidateId, (int)$categoryId);
                    }
                }
                
                $voterModel->markAsVoted($user['id']);
                (new AuditLog())->log($user['id'], 'voter', 'vote_cast', "Submitted votes");
                
                $db->commit();
                flash('success', 'Your votes have been recorded successfully!');
                Response::redirect($_SERVER['REQUEST_URI']);
            } catch (Exception $e) {
                $db->rollBack();
                flash('error', 'Failed to record votes. Please try again.');
            }
        }
    }
}

$remainingCategories = array_filter($categories, fn($cat) => !in_array($cat['id'], $votedCategories));
$hasVotedAll = empty($remainingCategories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= APP_URL ?>/public/index.php">
                <i class="fas fa-vote-yea me-2"></i><?= htmlspecialchars(APP_NAME) ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']) ?>
                </span>
                <a href="<?= APP_URL ?>/public/voter/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container py-4">
        <?php if (hasFlash('success')): ?>
            <div class="alert alert-success alert-custom alert-success-custom"><?= getFlash('success') ?></div>
        <?php endif; ?>
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-danger alert-custom alert-danger-custom"><?= getFlash('error') ?></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card-custom">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-info-circle me-2"></i>Voting Status</span>
                        <?php if ($isVotingOpen): ?>
                            <span class="badge bg-success"><i class="fas fa-unlock me-1"></i>Voting Open</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Voting Closed</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h3 class="text-primary"><?= count($categories) ?></h3>
                                <p class="mb-0 text-muted">Total Positions</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h3 class="text-success"><?= count($voterVotes) ?></h3>
                                <p class="mb-0 text-muted">Votes Cast</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h3 class="text-warning"><?= count($remainingCategories) ?></h3>
                                <p class="mb-0 text-muted">Remaining</p>
                            </div>
                        </div>
                        
                        <?php if ($voter['has_voted'] || $hasVotedAll): ?>
                            <div class="alert alert-success mt-3 mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>You have completed your voting!</strong> Thank you for participating in this election.
                            </div>
                        <?php elseif ($isVotingOpen): ?>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Select one candidate for each position, then click "Submit All Votes" when done.</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($isVotingOpen && !$hasVotedAll): ?>
            <form method="POST" id="votes-form">
                <?= CSRF::getField() ?>
                <input type="hidden" name="action" value="submit_all_votes">
                
                <div class="row">
                    <?php foreach ($categories as $category): ?>
                        <?php if (in_array($category['id'], $votedCategories)) continue; ?>
                        <div class="col-12 mb-4">
                            <div class="category-card" id="category-<?= $category['id'] ?>">
                                <div class="category-header bg-light p-3 rounded-top">
                                    <h5 class="mb-0">
                                        <i class="fas fa-tag me-2 text-primary"></i><?= htmlspecialchars($category['name']) ?>
                                        <?php if ($category['description']): ?>
                                            <small class="text-muted"> - <?= htmlspecialchars($category['description']) ?></small>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                
                                <?php if (empty($category['candidates'])): ?>
                                    <div class="p-4 text-center text-muted">
                                        No candidates available for this position.
                                    </div>
                                <?php else: ?>
                                    <div class="row g-3 p-3">
                                        <?php foreach ($category['candidates'] as $candidate): ?>
                                            <div class="col-lg-4 col-md-6">
                                                <div class="candidate-card h-100" id="candidate-<?= $candidate['id'] ?>">
                                                    <?php if (!empty($candidate['photo'])): ?>
                                                        <img src="<?= APP_URL ?>/public/<?= htmlspecialchars($candidate['photo']) ?>" 
                                                             class="candidate-photo" alt="<?= htmlspecialchars($candidate['first_name']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div class="candidate-photo d-flex align-items-center justify-content-center bg-light" style="display:none;">
                                                            <i class="fas fa-user fa-4x text-muted"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="candidate-photo d-flex align-items-center justify-content-center bg-light">
                                                            <i class="fas fa-user fa-4x text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="candidate-info">
                                                        <h5 class="candidate-name">
                                                            <?= htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) ?>
                                                        </h5>
                                                        <?php if ($candidate['party_group']): ?>
                                                            <p class="candidate-party"><?= htmlspecialchars($candidate['party_group']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($candidate['bio']): ?>
                                                            <p class="candidate-bio"><?= truncate(htmlspecialchars($candidate['bio']), 100) ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="select-btn" 
                                                                onclick="selectCandidate(<?= $category['id'] ?>, <?= $candidate['id'] ?>, '<?= htmlspecialchars(addslashes($candidate['first_name'] . ' ' . $candidate['last_name'])) ?>')">
                                                            <i class="fas fa-check-circle me-2"></i>Select
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="p-3 bg-light border-top">
                                        <span class="selected-candidate-label" id="selected-label-<?= $category['id'] ?>">
                                            <span class="text-muted">Please select a candidate above</span>
                                        </span>
                                        <input type="hidden" name="votes[<?= $category['id'] ?>]" id="selected-candidate-<?= $category['id'] ?>" value="">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mb-5">
                    <button type="submit" class="btn btn-success btn-lg" id="submit-all-btn" onclick="return confirmSubmit()">
                        <i class="fas fa-vote-yea me-2"></i>Submit All Votes
                    </button>
                    <p class="text-muted mt-2">
                        <small>You have selected <span id="selected-count">0</span> of <?= count($remainingCategories) ?> positions</small>
                    </p>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                <h3>Thank you for voting!</h3>
                <p class="text-muted">
                    <?php if ($hasVotedAll): ?>
                        You have successfully cast all your votes.
                    <?php else: ?>
                        Voting is currently closed. Please check back later.
                    <?php endif; ?>
                </p>
                <?php if ($settings['show_results']): ?>
                    <a href="<?= APP_URL ?>/public/results.php" class="btn btn-primary">
                        <i class="fas fa-chart-bar me-2"></i>View Results
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($voterVotes)): ?>
            <h4 class="mb-3 mt-5"><i class="fas fa-history me-2"></i>Your Votes</h4>
            <div class="card-custom">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Your Candidate</th>
                                    <th>Time Voted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($voterVotes as $vote): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($vote['category_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($vote['first_name'] . ' ' . $vote['last_name']) ?></td>
                                        <td><?= formatDateTime($vote['vote_timestamp']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedCount = 0;
        const totalCategories = <?= count($remainingCategories) ?>;
        
        function selectCandidate(categoryId, candidateId, candidateName) {
            document.querySelectorAll('#category-' + categoryId + ' .candidate-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('#category-' + categoryId + ' .select-btn').forEach(btn => {
                btn.classList.remove('selected');
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Select';
            });
            
            const card = document.getElementById('candidate-' + candidateId);
            card.classList.add('selected');
            
            const btn = card.querySelector('.select-btn');
            btn.classList.add('selected');
            btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Selected';
            
            document.getElementById('selected-candidate-' + categoryId).value = candidateId;
            document.getElementById('selected-label-' + categoryId).innerHTML = 
                '<strong>Selected:</strong> ' + candidateName;
            
            updateSubmitButton();
        }
        
        function updateSubmitButton() {
            selectedCount = document.querySelectorAll('input[name^="votes["][value!=""]').length;
            document.getElementById('selected-count').textContent = selectedCount;
        }
        
        function confirmSubmit() {
            return confirm('Are you sure you want to submit your votes? This action cannot be undone.');
        }
    </script>
</body>
</html>
