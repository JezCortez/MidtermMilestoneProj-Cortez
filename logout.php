<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

// Logging out changes state -> POST + CSRF token only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::valid()) {
    Auth::logout();
    Flash::add('success', 'You have been logged out.');
}
redirect('login.php');
