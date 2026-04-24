<?php
namespace App\Core;

class Request {
    public function __construct() {
        $this->sanitize();
    }

    public function getMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getPath(): string {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($uri, '/') ?: '/';
    }

    public function get(string $key, mixed $default = null): mixed {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed {
        return $_POST[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed {
        if ($this->getMethod() === 'GET') {
            return $this->get($key, $default);
        }
        return $this->post($key, $default);
    }

    public function all(): array {
        if ($this->getMethod() === 'GET') {
            return $_GET;
        }
        return $_POST;
    }

    public function has(string $key): bool {
        if ($this->getMethod() === 'GET') {
            return isset($_GET[$key]);
        }
        return isset($_POST[$key]);
    }

    public function file(string $key): ?array {
        return $_FILES[$key] ?? null;
    }

    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function getIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function getReferer(): ?string {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    private function sanitize(): void {
        if ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                if (is_string($value)) {
                    $_POST[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                }
            }
        }
    }
}
