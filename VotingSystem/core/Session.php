<?php
namespace App\Core;

class Session {
    private static bool $started = false;

    public static function start(): void {
        if (self::$started === false) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            self::$started = true;
            self::regenerate();
        }
    }

    public static function regenerate(): void {
        if (isset($_SESSION['last_regeneration'])) {
            $interval = 300;
            if (time() - $_SESSION['last_regeneration'] < $interval) {
                return;
            }
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['last_regeneration'] = time();
    }

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        if (self::$started && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
            self::$started = false;
        }
    }

    public static function setFlash(string $key, mixed $value): void {
        $_SESSION['flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed {
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    public static function setUserData(string $key, mixed $value): void {
        $_SESSION['user_data'][$key] = $value;
    }

    public static function getUserData(string $key, mixed $default = null): mixed {
        return $_SESSION['user_data'][$key] ?? $default;
    }

    public static function getId(): string {
        return session_id();
    }

    public static function getIpAddress(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
