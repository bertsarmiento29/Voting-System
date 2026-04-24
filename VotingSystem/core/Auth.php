<?php
namespace App\Core;

use App\Models\User;
use App\Models\Voter;

class Auth {
    public static function attempt(string $email, string $password, string $type = 'admin'): bool {
        $model = $type === 'admin' ? new User() : new Voter();
        $user = $model->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        if (!$user['is_active']) {
            return false;
        }

        self::login($user, $type);
        return true;
    }

    public static function login(array $user, string $type = 'admin'): void {
        Session::regenerate();
        
        $sessionData = [
            'id' => $user['id'],
            'email' => $user['email'],
            'type' => $type,
            'logged_in_at' => time()
        ];

        if ($type === 'admin') {
            $sessionData['username'] = $user['username'];
            $sessionData['full_name'] = $user['full_name'];
            $sessionData['role'] = $user['role'];
        } else {
            $sessionData['voter_id'] = $user['voter_id'];
            $sessionData['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        }

        Session::set('auth', $sessionData);
        
        if ($type === 'admin') {
            (new User())->updateLastLogin($user['id']);
        } else {
            (new Voter())->updateLastLogin($user['id']);
        }
    }

    public static function logout(): void {
        Session::destroy();
    }

    public static function check(): bool {
        return Session::has('auth');
    }

    public static function guest(): bool {
        return !self::check();
    }

    public static function user(): ?array {
        return Session::get('auth');
    }

    public static function id(): ?int {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function type(): ?string {
        return Session::get('auth')['type'] ?? null;
    }

    public static function isAdmin(): bool {
        return self::type() === 'admin';
    }

    public static function isVoter(): bool {
        return self::type() === 'voter';
    }

    public static function hasRole(string $role): bool {
        $user = self::user();
        return $user && ($user['role'] ?? '') === $role;
    }

    public static function requireAuth(string $type = 'admin'): void {
        if (!self::check() || self::type() !== $type) {
            self::redirectToLogin($type);
        }
    }

    public static function redirectToLogin(string $type = 'admin'): void {
        $redirect = $type === 'admin' ? '/public/admin/login.php' : '/public/voter/login.php';
        header('Location: ' . APP_URL . $redirect);
        exit;
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_DEFAULT_ROUNDS]);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
