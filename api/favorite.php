<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

/**
 * AJAX endpoint behind the ♥ Save button.
 * POST: recipe_id, action = add | remove   (CSRF token in the X-CSRF-Token header)
 * Replies with JSON so the page can update without reloading.
 */

header('Content-Type: application/json; charset=utf-8');

function reply(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ['ok' => false, 'error' => 'POST only.']);
}
if (!Auth::check()) {
    reply(401, ['ok' => false, 'error' => 'Please log in.']);
}
if (!Csrf::valid()) {
    reply(403, ['ok' => false, 'error' => 'Invalid request token. Reload the page.']);
}

$recipeId = post_id('recipe_id');
$action   = (string) ($_POST['action'] ?? '');

if ($recipeId === null || !in_array($action, ['add', 'remove'], true)) {
    reply(422, ['ok' => false, 'error' => 'Invalid request.']);
}
if (!(new RecipeModel($pdo))->exists($recipeId)) {
    reply(404, ['ok' => false, 'error' => 'That recipe no longer exists.']);
}

$favorites = new FavoriteModel($pdo);
if ($action === 'add') {
    $favorites->add((int) Auth::id(), $recipeId);
} else {
    $favorites->remove((int) Auth::id(), $recipeId);
}

reply(200, [
    'ok'        => true,
    'favorited' => $action === 'add',
    'count'     => $favorites->countFor($recipeId),
]);
