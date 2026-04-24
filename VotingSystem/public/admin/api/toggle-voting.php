<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Session.php';
require_once __DIR__ . '/../../../core/CSRF.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../app/Models/ElectionSetting.php';
require_once __DIR__ . '/../../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\CSRF;
use App\Core\Auth;
use App\Core\Response;
use App\Models\ElectionSetting;
use App\Models\AuditLog;

Session::start();
Auth::requireAuth('admin');

$settingsModel = new ElectionSetting();
$settings = $settingsModel->get();

if ($settings['allow_voting']) {
    $settingsModel->disableVoting();
    (new AuditLog())->log(Auth::id(), 'admin', 'stop_voting', 'Voting stopped');
} else {
    $settingsModel->enableVoting();
    (new AuditLog())->log(Auth::id(), 'admin', 'start_voting', 'Voting started');
}

header('Location: ' . APP_URL . '/public/admin/election-settings.php');
exit;
