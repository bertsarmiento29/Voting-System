<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/CSRF.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../app/Models/Voter.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\CSRF;
use App\Core\Auth;
use App\Core\Response;
use App\Models\Voter;
use App\Models\AuditLog;

Session::start();

if (Auth::check() && Auth::isVoter()) {
    Response::redirect(APP_URL . '/public/voter/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateRequest()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $voterId = trim($_POST['voter_id'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($voterId) || empty($password)) {
            $error = 'Please enter your Voter ID and password.';
        } else {
            $voterModel = new Voter();
            $voter = $voterModel->findByVoterId($voterId);
            
            if ($voter && password_verify($password, $voter['password'])) {
                if (!$voter['is_active']) {
                    $error = 'Your account has been deactivated. Please contact administrator.';
                } else {
                    Auth::login($voter, 'voter');
                    (new AuditLog())->log($voter['id'], 'voter', 'login', 'Voter logged in');
                    Response::redirect(APP_URL . '/public/voter/dashboard.php');
                }
            } else {
                $error = 'Invalid Voter ID or password.';
            }
        }
    }
}

$settings = App\Core\Database::getInstance()->selectOne("SELECT * FROM election_settings LIMIT 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-vote-yea fa-3x mb-3"></i>
            <h2>Voter Login</h2>
            <p class="mb-0 opacity-75">Cast your vote securely</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-custom alert-danger-custom">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!($settings['allow_voting'] ?? false)): ?>
                <div class="alert alert-warning alert-custom alert-warning-custom mb-3">
                    <i class="fas fa-clock me-2"></i>Voting is currently closed.
                </div>
            <?php endif; ?>
            
            <form method="POST" class="needs-validation" novalidate>
                <?= CSRF::getField() ?>
                
                <div class="mb-4">
                    <label for="voter_id" class="form-label">Voter ID</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-id-card text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="voter_id" name="voter_id" 
                               value="<?= htmlspecialchars($_POST['voter_id'] ?? '') ?>" 
                               placeholder="Enter your Voter ID" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <button type="button" class="btn btn-outline-secondary border-start-0" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login w-100 mb-3" id="loginBtn" <?= !($settings['allow_voting'] ?? false) ? 'disabled' : '' ?>>
                    <span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Login to Vote</span>
                    <span class="loading-spinner d-none"></span>
                </button>
                
                <div class="text-center">
                    <a href="<?= APP_URL ?>/public/index.php" class="text-muted">
                        <i class="fas fa-arrow-left me-1"></i>Back to Home
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            if (!btn.disabled) {
                btn.querySelector('.btn-text').textContent = 'Logging in...';
                btn.querySelector('.loading-spinner').classList.remove('d-none');
                btn.disabled = true;
            }
        });
    </script>
</body>
</html>
