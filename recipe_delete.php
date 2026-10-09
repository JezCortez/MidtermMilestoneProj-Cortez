<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::valid()) {
    Flash::add('error', 'Invalid request.');
    redirect('index.php');
}

$id = post_id();
if ($id === null) {
    Flash::add('error', 'Invalid recipe.');
    redirect('index.php');
}

// user_id is part of the DELETE, so only the owner can remove it
if ((new RecipeModel($pdo))->delete($id, (int) Auth::id())) {
    Flash::add('success', 'Recipe deleted.');
} else {
    Flash::add('error', 'You can only delete your own recipes.');
}
redirect('index.php');
