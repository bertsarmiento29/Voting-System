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

if ($settings['show_results']) {
    $settingsModel->disableResults();
    (new AuditLog())->log(Auth::id(), 'admin', 'hide_results', 'Results hidden');
} else {
    $settingsModel->enableResults();
    (new AuditLog())->log(Auth::id(), 'admin', 'show_results', 'Results published');
}

header('Location: ' . APP_URL . '/public/admin/election-settings.php');
exit;
