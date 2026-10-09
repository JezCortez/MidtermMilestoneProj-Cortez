<?php
declare(strict_types=1);

/**
 * bootstrap.php — every page starts with:  require_once __DIR__ . '/bootstrap.php';
 * It loads db.php (the single connection file), the helper functions,
 * the class autoloader, starts the session and gives the page a $pdo.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

Auth::start();
$pdo = Database::connect();
