<?php
declare(strict_types=1);

/** CSRF protection: one random token per session, checked on every POST. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    /** Accepts the token from a form field or from the X-CSRF-Token header (AJAX). */
    public static function valid(): bool
    {
        $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return is_string($sent) && hash_equals(self::token(), $sent);
    }
}
