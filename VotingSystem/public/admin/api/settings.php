<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Session.php';
require_once __DIR__ . '/../../../core/CSRF.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../app/Models/ElectionSetting.php';

use App\Core\Session;
use App\Core\CSRF;
use App\Core\Auth;
use App\Models\ElectionSetting;

Session::start();
Auth::requireAuth('admin');

if (!CSRF::validateRequest()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request']);
}

$data = json_decode(file_get_contents('php://input'), true);
$setting = $data['setting'] ?? '';
$value = (bool) ($data['value'] ?? false);

$settingsModel = new ElectionSetting();

switch ($setting) {
    case 'is_active':
    case 'allow_voting':
    case 'show_results':
        $settingsModel->update([$setting => $value ? 1 : 0]);
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid setting']);
}

jsonResponse(['success' => true, 'message' => 'Setting updated']);
