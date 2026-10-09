<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireLogin();   // MEMBER-only page

$recipes  = new RecipeModel($pdo);
$comments = new CommentModel($pdo);

$id = get_id();
$recipe = $id ? $recipes->find($id, (int) Auth::id()) : null;
if (!$recipe) {
    Flash::add('error', 'That recipe could not be found.');
    redirect('index.php');
}

$me          = (int) Auth::id();
$isOwner     = (int) $recipe['user_id'] === $me;
$v           = new Validator();
$commentBody = '';

// ---- add a comment ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid()) {
        Flash::add('error', 'Your session expired. Please try again.');
        redirect('recipe.php?id=' . $id);
    }

    $commentBody = trim((string) ($_POST['body'] ?? ''));
    $v->text('body', $commentBody, 'Comment', 1, 1000);

    if ($v->passes()) {
        $comments->create($id, $me, $commentBody);
        Flash::add('success', 'Comment added.');
        redirect('recipe.php?id=' . $id . '#comments');
    }
}

$ingredients  = $recipes->ingredients($id);
$steps        = preg_split('/\R/', $recipe['steps']) ?: [];
$commentList  = $comments->forRecipe($id);

$pageTitle    = $recipe['title'];
$extraScripts = ['favorites.js', 'scaler.js'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="index.php">&larr; All recipes</a></p>

<article class="card">
    <header class="post-head">
        <div>
            <p class="tag"><?= e($recipe['category']) ?></p>
            <h1 class="recipe-title">
                <?= e($recipe['title']) ?>
                <?php if ($recipe['edited_at'] !== null): ?>
                    <span class="tag-edited" title="Edited <?= e(format_date($recipe['edited_at'])) ?>">Edited</span>
                <?php endif; ?>
            </h1>
            <p class="meta">by <strong><?= e($recipe['author']) ?></strong> · <?= e(format_date($recipe['created_at'])) ?></p>
        </div>
        <div class="actions">
            <?= fav_button((int) $recipe['id'], (bool) $recipe['is_fav'], (int) $recipe['fav_count']) ?>
            <?php if ($isOwner): ?>
                <a class="btn btn-sm" href="recipe_form.php?id=<?= (int) $recipe['id'] ?>">Edit</a>
                <form method="post" action="recipe_delete.php" class="inline-form"
                      data-confirm="Delete this recipe? Its comments will be deleted too.">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $recipe['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <p class="recipe-descr"><?= e($recipe['description']) ?></p>
</article>

<!-- ===== Ingredients with the servings scaler + US/metric converter ===== -->
<section class="card" id="scaler" data-base-servings="<?= (int) $recipe['base_servings'] ?>">
    <h2>Ingredients</h2>

    <!-- shown by scaler.js (needs JavaScript) -->
    <div class="scaler-controls" hidden>
        <div class="servings-control">
            <label for="servings">Servings</label>
            <button type="button" class="btn btn-sm" id="serv-dec" aria-label="Fewer servings">&minus;</button>
            <input type="number" id="servings" min="1" max="100" step="1"
                   value="<?= (int) $recipe['base_servings'] ?>">
            <button type="button" class="btn btn-sm" id="serv-inc" aria-label="More servings">+</button>
        </div>
        <div class="unit-control" role="group" aria-label="Measurement system">
            <button type="button" class="seg is-active" data-system="original">As written</button>
            <button type="button" class="seg" data-system="metric">Metric</button>
            <button type="button" class="seg" data-system="us">US</button>
        </div>
    </div>
    <p class="meta">Written for <?= (int) $recipe['base_servings'] ?> serving<?= (int) $recipe['base_servings'] === 1 ? '' : 's' ?>.</p>

    <ul class="ingredient-list" id="ingredient-list">
        <?php foreach ($ingredients as $ing): ?>
            <li data-qty="<?= e($ing['quantity'] === null ? '' : fmt_qty($ing['quantity'])) ?>"
                data-unit="<?= e((string) $ing['unit']) ?>">
                <span class="amount"><?= e(trim(fmt_qty($ing['quantity']) . ' ' . Units::label($ing['unit'], (float) $ing['quantity']))) ?></span>
                <span class="ing-name"><?= e($ing['name']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="card">
    <h2>Steps</h2>
    <ol class="steps">
        <?php foreach ($steps as $step): ?>
            <li><?= e($step) ?></li>
        <?php endforeach; ?>
    </ol>
</section>

<!-- ===== Comments ===== -->
<section class="card" id="comments">
    <h2><?= count($commentList) ?> comment<?= count($commentList) === 1 ? '' : 's' ?></h2>

    <?php foreach ($commentList as $c): ?>
        <div class="comment" id="comment-<?= (int) $c['id'] ?>">
            <div class="comment-head">
                <p class="meta">
                    <strong><?= e($c['author']) ?></strong> · <?= e(format_date($c['created_at'])) ?>
                    <?php if ($c['edited_at'] !== null): ?>
                        <span class="tag-edited" title="Edited <?= e(format_date($c['edited_at'])) ?>">Edited</span>
                    <?php endif; ?>
                </p>
                <?php if ((int) $c['user_id'] === $me): ?>
                    <div class="actions">
                        <a class="btn btn-xs" href="comment_edit.php?id=<?= (int) $c['id'] ?>">Edit</a>
                        <form method="post" action="comment_delete.php" class="inline-form" data-confirm="Delete this comment?">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <p class="comment-body"><?= nl2br(e($c['body'])) ?></p>
        </div>
    <?php endforeach; ?>

    <form method="post" action="recipe.php?id=<?= (int) $recipe['id'] ?>#comments" class="comment-form" novalidate data-validate>
        <?= Csrf::field() ?>
        <div class="field">
            <label for="body">Leave a comment</label>
            <textarea id="body" name="body" rows="3" required maxlength="1000"
                      data-label="Comment" data-min="1" data-max="1000"
                      placeholder="Did you try it? How did it turn out?"><?= e($commentBody) ?></textarea>
            <p class="field-error"><?= e($v->first('body')) ?></p>
        </div>
        <button type="submit" class="btn btn-primary">Post comment</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
