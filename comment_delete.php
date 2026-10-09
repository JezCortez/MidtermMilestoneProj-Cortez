<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::valid()) {
    Flash::add('error', 'Invalid request.');
    redirect('index.php');
}

$comments = new CommentModel($pdo);
$id       = post_id();
$comment  = $id ? $comments->find($id) : null;

if (!$comment) {
    Flash::add('error', 'That comment no longer exists.');
    redirect('index.php');
}

if ($comments->delete($id, (int) Auth::id())) {
    Flash::add('success', 'Comment deleted.');
} else {
    Flash::add('error', 'You can only delete your own comments.');
}
redirect('recipe.php?id=' . (int) $comment['recipe_id'] . '#comments');
