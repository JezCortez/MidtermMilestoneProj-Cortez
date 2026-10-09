<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireLogin();

$comments = new CommentModel($pdo);
$id       = get_id() ?? post_id();
$comment  = $id ? $comments->find($id) : null;

if (!$comment) {
    Flash::add('error', 'That comment no longer exists.');
    redirect('index.php');
}
if ((int) $comment['user_id'] !== Auth::id()) {
    http_response_code(403);
    Flash::add('error', 'You can only edit your own comments.');
    redirect('recipe.php?id=' . (int) $comment['recipe_id'] . '#comment-' . $id);
}

$v    = new Validator();
$body = $comment['body'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid()) {
        $v->add('form', 'Your session expired. Please try again.');
    }

    $body = trim((string) ($_POST['body'] ?? ''));
    $v->text('body', $body, 'Comment', 1, 1000);

    if ($v->passes()) {
        if ($body === $comment['body']) {
            Flash::add('info', 'No changes were made.');
        } else {
            $comments->update($id, (int) Auth::id(), $body);   // sets edited_at
            Flash::add('success', 'Comment updated.');
        }
        redirect('recipe.php?id=' . (int) $comment['recipe_id'] . '#comment-' . $id);
    }
}

$pageTitle = 'Edit comment';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Edit comment</h1>
    <p class="meta">On: <strong><?= e($comment['recipe_title']) ?></strong></p>
    <?php if ($v->has('form')): ?><div class="alert alert-error"><?= e($v->first('form')) ?></div><?php endif; ?>

    <form method="post" action="comment_edit.php?id=<?= (int) $id ?>" novalidate data-validate>
        <?= Csrf::field() ?>
        <div class="field">
            <label for="body">Comment</label>
            <textarea id="body" name="body" rows="4" required maxlength="1000"
                      data-label="Comment" data-min="1" data-max="1000"><?= e($body) ?></textarea>
            <p class="field-error"><?= e($v->first('body')) ?></p>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a class="btn" href="recipe.php?id=<?= (int) $comment['recipe_id'] ?>#comment-<?= (int) $id ?>">Cancel</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
