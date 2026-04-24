<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\AuditLog;

Session::start();

if (Auth::check()) {
    $user = Auth::user();
    (new AuditLog())->log($user['id'], 'admin', 'logout', 'Admin logged out');
}

Auth::logout();

header('Location: ' . APP_URL . '/public/admin/login.php');
exit;
