<?php
define('APP_NAME', 'Online Voting System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/VotingSystem');
define('APP_TIMEZONE', 'Asia/Manila');

date_default_timezone_set(APP_TIMEZONE);

define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_LIFETIME', 7200);

define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5242880);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_DEFAULT_ROUNDS', 12);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
