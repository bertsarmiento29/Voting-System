<?php
namespace App\Core;

class CSRF {
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_LENGTH = 32;

    public static function generateToken(): string {
        if (!Session::has(self::TOKEN_NAME)) {
            $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
            Session::set(self::TOKEN_NAME, [
                'token' => $token,
                'expires' => time() + CSRF_TOKEN_LIFETIME
            ]);
        }
        return Session::get(self::TOKEN_NAME)['token'];
    }

    public static function getToken(): string {
        return self::generateToken();
    }

    private static function getStoredToken(): ?string {
        if (!Session::has(self::TOKEN_NAME)) {
            return null;
        }
        $tokenData = Session::get(self::TOKEN_NAME);
        if ($tokenData['expires'] < time()) {
            Session::remove(self::TOKEN_NAME);
            return null;
        }
        return $tokenData['token'];
    }

    public static function validateToken(?string $token): bool {
        if ($token === null) {
            return false;
        }
        $storedToken = self::getStoredToken();
        if ($storedToken === null) {
            return false;
        }
        return hash_equals($storedToken, $token);
    }

    public static function getField(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::generateToken() . '">';
    }

    public static function getHeader(): ?string {
        return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRFTOKEN'] ?? null;
    }

    public static function validateRequest(): bool {
        $headerToken = self::getHeader();
        $formToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        $token = $headerToken ?? $formToken;
        return self::validateToken($token);
    }

    public static function invalidate(): void {
        Session::remove(self::TOKEN_NAME);
    }
}
