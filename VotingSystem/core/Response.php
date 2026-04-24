<?php
namespace App\Core;

class Response {
    public static function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url, int $status = 302): void {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function back(): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }

    public static function refresh(): void {
        self::redirect($_SERVER['REQUEST_URI']);
    }

    public static function view(string $view, array $data = [], int $status = 200): void {
        http_response_code($status);
        extract($data);
        $viewPath = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            self::json(['error' => 'View not found'], 404);
        }
    }

    public static function abort(int $status = 404, string $message = ''): void {
        http_response_code($status);
        if ($status === 404) {
            echo '<h1>404 - Page Not Found</h1>';
            if ($message) {
                echo '<p>' . htmlspecialchars($message) . '</p>';
            }
        } elseif ($status === 403) {
            echo '<h1>403 - Forbidden</h1>';
            if ($message) {
                echo '<p>' . htmlspecialchars($message) . '</p>';
            }
        }
        exit;
    }

    public static function success(string $message = 'Success', array $data = []): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function error(string $message = 'Error', int $status = 400): void {
        self::json([
            'success' => false,
            'message' => $message
        ], $status);
    }

    public static function setHeader(string $name, string $value): void {
        header("$name: $value");
    }

    public static function setContentType(string $type): void {
        self::setHeader('Content-Type', $type);
    }

    public static function noCache(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
