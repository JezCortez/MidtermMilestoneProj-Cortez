<?php
declare(strict_types=1);

/** Session handling and the guest / member guards. */
final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']),
            ]);
            session_start();
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    public static function name(): string
    {
        return (string) ($_SESSION['user_name'] ?? '');
    }

    public static function login(int $id, string $name): void
    {
        session_regenerate_id(true);   // prevents session fixation
        $_SESSION['user_id']   = $id;
        $_SESSION['user_name'] = $name;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();               // fresh session to carry a flash message
    }

    /** Member-only pages: guests go to the login page. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    /** Guest-only pages (login / register): members go to the recipe list. */
    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('index.php');
        }
    }
}
