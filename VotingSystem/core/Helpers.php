<?php

function config(string $key, mixed $default = null): mixed {
    static $config = null;
    if ($config === null) {
        $configPath = __DIR__ . '/../config/config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
        }
    }
    return $config[$key] ?? $default;
}

function view(string $view, array $data = []): void {
    extract($data);
    $viewPath = __DIR__ . '/../../resources/views/' . str_replace('.', '/', $view) . '.php';
    if (file_exists($viewPath)) {
        require $viewPath;
    } else {
        echo "View not found: $view";
    }
}

function dd(mixed $value): void {
    echo '<pre>';
    var_dump($value);
    exit;
}

function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function back(): void {
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($referer);
}

function old(string $key, mixed $default = ''): mixed {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function asset(string $path): string {
    return APP_URL . '/public/' . ltrim($path, '/');
}

function route(string $name, array $params = []): string {
    $routes = [
        'home' => '/',
        'login' => '/public/admin/login.php',
        'voter.login' => '/public/voter/login.php',
        'admin.dashboard' => '/public/admin/dashboard.php',
        'voter.dashboard' => '/public/voter/dashboard.php',
        'vote' => '/public/voter/vote.php',
        'results' => '/public/results.php',
    ];
    $url = $routes[$name] ?? '#';
    if ($params) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function flash(string $key, string $message): void {
    \App\Core\Session::setFlash($key, $message);
}

function getFlash(string $key): ?string {
    return \App\Core\Session::getFlash($key);
}

function hasFlash(string $key): bool {
    return \App\Core\Session::has('flash') && isset($_SESSION['flash'][$key]);
}

function formatDate(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

function formatDateTime(string $datetime, string $format = 'M d, Y h:i A'): string {
    return date($format, strtotime($datetime));
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return formatDate($datetime);
}

function randomString(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function uploadFile(array $file, string $directory = 'uploads/', array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return null;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return null;
    }

    $filename = time() . '_' . randomString(8) . '.' . $extension;
    $uploadPath = __DIR__ . '/../../public/' . $directory;
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $destination = $uploadPath . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $directory . $filename;
    }
    
    return null;
}

function numberFormat(int|float $number): string {
    return number_format($number, 0);
}

function percentage(int $value, int $total): float {
    if ($total === 0) return 0;
    return round(($value / $total) * 100, 1);
}

function truncate(string $text, int $length = 100): string {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
