<?php /** Expects: $r (a row from RecipeModel::search/favoritesOf), $removeCard (bool). */ ?>
<article class="card recipe-card" id="recipe-<?= (int) $r['id'] ?>">
    <p class="tag"><?= e($r['category']) ?></p>
    <h3 class="card-title">
        <a href="recipe.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a>
        <?php if ($r['edited_at'] !== null): ?><span class="tag-edited">Edited</span><?php endif; ?>
    </h3>
    <p class="meta">by <?= e($r['author']) ?> · <?= e(format_date($r['created_at'])) ?></p>
    <p class="card-descr"><?= e($r['description']) ?></p>
    <div class="card-foot">
        <span class="meta"><?= (int) $r['comment_count'] ?> comment<?= (int) $r['comment_count'] === 1 ? '' : 's' ?></span>
        <?= fav_button((int) $r['id'], (bool) $r['is_fav'], (int) $r['fav_count'], $removeCard ?? false) ?>
    </div>
</article>
