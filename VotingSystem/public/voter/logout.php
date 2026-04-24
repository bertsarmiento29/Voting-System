<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../app/Models/AuditLog.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\AuditLog;

Session::start();

if (Auth::check() && Auth::isVoter()) {
    $user = Auth::user();
    (new AuditLog())->log($user['id'], 'voter', 'logout', 'Voter logged out');
}

Auth::logout();

header('Location: ' . APP_URL . '/public/voter/login.php');
exit;
