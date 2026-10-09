<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireLogin();   // MEMBER-only page

$recipes = (new RecipeModel($pdo))->favoritesOf((int) Auth::id());

$pageTitle = 'My favorites';
$extraScripts = ['favorites.js'];
require __DIR__ . '/includes/header.php';
?>
<h1>My favorites</h1>

<p class="empty" id="favorites-empty" <?= $recipes ? 'hidden' : '' ?>>
    You haven't saved any recipes yet. <a href="index.php">Browse recipes</a>
</p>

<div class="recipe-grid" id="favorites-grid">
    <?php foreach ($recipes as $r): $removeCard = true; include __DIR__ . '/includes/recipe_card.php'; endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
