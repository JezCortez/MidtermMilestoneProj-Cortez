<?php
declare(strict_types=1);

/** Collects validation errors (server side). The browser re-checks the same rules in validation.js. */
final class Validator
{
    /** @var array<string,string> field key => first error message */
    private array $errors = [];

    public function add(string $key, string $message): void
    {
        $this->errors[$key] ??= $message;
    }

    public function has(string $key): bool
    {
        return isset($this->errors[$key]);
    }

    public function first(string $key): string
    {
        return $this->errors[$key] ?? '';
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** Required text with a length range (in characters). */
    public function text(string $key, string $value, string $label, int $min, int $max): void
    {
        $len = mb_strlen($value);
        if ($value === '') {
            $this->add($key, "$label is required.");
        } elseif ($len < $min) {
            $this->add($key, "$label must be at least $min characters.");
        } elseif ($len > $max) {
            $this->add($key, "$label must be at most $max characters.");
        }
    }

    public function email(string $key, string $value): void
    {
        if ($value === '') {
            $this->add($key, 'Email is required.');
        } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 255) {
            $this->add($key, 'Enter a valid email address.');
        }
    }

    public function password(string $key, string $value): void
    {
        if ($value === '') {
            $this->add($key, 'Password is required.');
        } elseif (strlen($value) < 8) {
            $this->add($key, 'Password must be at least 8 characters.');
        } elseif (strlen($value) > 72) {
            $this->add($key, 'Password must be at most 72 characters.');
        } elseif (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
            $this->add($key, 'Password must contain at least one letter and one number.');
        }
    }
}
