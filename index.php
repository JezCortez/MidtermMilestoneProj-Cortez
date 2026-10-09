<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireLogin();   // MEMBER-only page

const PER_PAGE = 9;

$categoryModel = new CategoryModel($pdo);
$recipes       = new RecipeModel($pdo);
$categories    = $categoryModel->all();

// ---- read the search / filter inputs from the URL ----
$q    = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$cat  = get_id('category');
if ($cat !== null && !in_array($cat, array_column($categories, 'id'), true)) {
    $cat = null;   // ignore ids that are not real categories
}
$mine = ($_GET['mine'] ?? '') === '1';
$page = max(1, (int) ($_GET['page'] ?? 1));

$result     = $recipes->search((int) Auth::id(), $q, $cat, $mine, $page, PER_PAGE);
$totalPages = max(1, (int) ceil($result['total'] / PER_PAGE));
if ($page > $totalPages) {
    $page   = $totalPages;
    $result = $recipes->search((int) Auth::id(), $q, $cat, $mine, $page, PER_PAGE);
}

/** Link to another page of the same search. */
function page_url(int $p, string $q, ?int $cat, bool $mine): string
{
    $query = array_filter(
        ['q' => $q, 'category' => $cat, 'mine' => $mine ? '1' : null, 'page' => $p > 1 ? $p : null],
        fn($v) => $v !== null && $v !== ''
    );
    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}

$filtering = $q !== '' || $cat !== null || $mine;
$pageTitle = $mine ? 'My recipes' : 'Browse recipes';
$extraScripts = ['favorites.js'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= $mine ? 'My recipes' : 'Latest recipes' ?></h1>

<form method="get" action="index.php" class="search-form card" role="search">
    <?php if ($mine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
    <div class="search-row">
        <div class="field">
            <label for="q">Search</label>
            <input type="text" id="q" name="q" value="<?= e($q) ?>" maxlength="100"
                   placeholder="Title, description or ingredient">
        </div>
        <div class="field">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-buttons">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($filtering): ?><a class="btn" href="index.php">Clear</a><?php endif; ?>
        </div>
    </div>
</form>

<?php if ($filtering): ?>
    <p class="meta"><?= $result['total'] ?> recipe<?= $result['total'] === 1 ? '' : 's' ?> found</p>
<?php endif; ?>

<?php if (!$result['rows']): ?>
    <p class="empty">
        <?= $filtering ? 'No recipes match that search.' : 'No recipes yet.' ?>
        <a href="recipe_form.php">Share the first one!</a>
    </p>
<?php else: ?>
    <div class="recipe-grid">
        <?php foreach ($result['rows'] as $r): $removeCard = false; include __DIR__ . '/includes/recipe_card.php'; endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pager" aria-label="Pages">
            <?php if ($page > 1): ?>
                <a class="btn btn-sm" href="<?= e(page_url($page - 1, $q, $cat, $mine)) ?>">&larr; Newer</a>
            <?php endif; ?>
            <span class="meta">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a class="btn btn-sm" href="<?= e(page_url($page + 1, $q, $cat, $mine)) ?>">Older &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
