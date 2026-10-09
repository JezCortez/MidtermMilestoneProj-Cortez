<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireLogin();   // MEMBER-only page

$categoryModel = new CategoryModel($pdo);
$recipes       = new RecipeModel($pdo);
$categories    = $categoryModel->all();
$form          = new RecipeForm($categoryModel);

// ---- create (no id) or edit (?id=...) ----
$id     = get_id() ?? post_id();
$recipe = null;
$oldIngredients = [];

if ($id !== null) {
    $recipe = $recipes->find($id, (int) Auth::id());
    if (!$recipe) {
        Flash::add('error', 'That recipe no longer exists.');
        redirect('index.php');
    }
    if ((int) $recipe['user_id'] !== Auth::id()) {          // OWNERSHIP check (GET and POST)
        http_response_code(403);
        Flash::add('error', 'You can only edit your own recipes.');
        redirect('recipe.php?id=' . $id);
    }
    $oldIngredients = $recipes->ingredients($id);

    // pre-fill the form with the saved recipe
    $form->data = [
        'title'         => $recipe['title'],
        'description'   => $recipe['description'],
        'category_id'   => (int) $recipe['category_id'],
        'base_servings' => (int) $recipe['base_servings'],
        'steps'         => $recipe['steps'],
    ];
    $form->rows = array_map(fn($i) => [
        'quantity' => fmt_qty($i['quantity']),
        'unit'     => (string) $i['unit'],
        'name'     => $i['name'],
    ], $oldIngredients);
}

$v = $form->v;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid()) {
        $v->add('form', 'Your session expired. Please try again.');
    }

    $form->handle($_POST);

    if ($v->passes()) {
        if ($recipe === null) {
            $newId = $recipes->create((int) Auth::id(), $form->data, $form->ingredients);
            Flash::add('success', 'Your recipe was shared.');
            redirect('recipe.php?id=' . $newId);
        }

        if (RecipeModel::isSame($recipe, $oldIngredients, $form->data, $form->ingredients)) {
            Flash::add('info', 'No changes were made.');          // no "Edited" tag if nothing changed
        } elseif ($recipes->update($id, (int) Auth::id(), $form->data, $form->ingredients)) {
            Flash::add('success', 'Recipe updated.');
        } else {
            Flash::add('error', 'You can only edit your own recipes.');
        }
        redirect('recipe.php?id=' . $id);
    }
}

$d = $form->data;
$editing = $recipe !== null;

/** One ingredient row (also used for the <template> the "Add ingredient" button clones). */
function ingredient_row(string|int $i, array $row, ?Validator $v): string
{
    ob_start(); ?>
    <div class="ing-row">
        <div class="cell cell-qty">
            <input type="number" name="ingredients[<?= $i ?>][quantity]" value="<?= e($row['quantity']) ?>"
                   step="any" min="0" max="<?= RecipeForm::QTY_MAX ?>" placeholder="Amount" aria-label="Amount"
                   data-number data-gt="0" data-maxval="<?= RecipeForm::QTY_MAX ?>" data-label="Amount">
            <p class="field-error"><?= e($v ? $v->first("ing_{$i}_quantity") : '') ?></p>
        </div>
        <div class="cell cell-unit">
            <select name="ingredients[<?= $i ?>][unit]" aria-label="Unit">
                <option value="">Unit</option>
                <?php foreach (Units::GROUPS as $group => $units): ?>
                    <optgroup label="<?= e($group) ?>">
                        <?php foreach ($units as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $row['unit'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <p class="field-error"><?= e($v ? $v->first("ing_{$i}_unit") : '') ?></p>
        </div>
        <div class="cell cell-name">
            <input type="text" name="ingredients[<?= $i ?>][name]" value="<?= e($row['name']) ?>"
                   required maxlength="<?= RecipeForm::ING_NAME_MAX ?>" placeholder="Ingredient" aria-label="Ingredient name"
                   data-label="Ingredient name" data-min="1" data-max="<?= RecipeForm::ING_NAME_MAX ?>">
            <p class="field-error"><?= e($v ? $v->first("ing_{$i}_name") : '') ?></p>
        </div>
        <button type="button" class="btn btn-sm remove-ing" aria-label="Remove this ingredient">&times;</button>
    </div>
    <?php
    return (string) ob_get_clean();
}

$pageTitle    = $editing ? 'Edit recipe' : 'Share a recipe';
$extraScripts = ['ingredients.js'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= $editing ? 'Edit recipe' : 'Share a recipe' ?></h1>

<form method="post" action="recipe_form.php<?= $editing ? '?id=' . (int) $id : '' ?>" class="card" novalidate data-validate>
    <?= Csrf::field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
    <?php if ($v->has('form')): ?><div class="alert alert-error"><?= e($v->first('form')) ?></div><?php endif; ?>

    <div class="field">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($d['title']) ?>" required
               maxlength="<?= RecipeForm::TITLE_MAX ?>" data-label="Title"
               data-min="<?= RecipeForm::TITLE_MIN ?>" data-max="<?= RecipeForm::TITLE_MAX ?>">
        <p class="field-error"><?= e($v->first('title')) ?></p>
    </div>

    <div class="field">
        <label for="description">Short description</label>
        <textarea id="description" name="description" rows="2" required
                  maxlength="<?= RecipeForm::DESC_MAX ?>" data-label="Description"
                  data-min="<?= RecipeForm::DESC_MIN ?>" data-max="<?= RecipeForm::DESC_MAX ?>"><?= e($d['description']) ?></textarea>
        <p class="field-error"><?= e($v->first('description')) ?></p>
    </div>

    <div class="two-cols">
        <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required data-label="Category">
                <option value="">Choose a category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $d['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="field-error"><?= e($v->first('category_id')) ?></p>
        </div>

        <div class="field">
            <label for="base_servings">Servings the amounts are for</label>
            <input type="number" id="base_servings" name="base_servings" value="<?= (int) $d['base_servings'] ?>"
                   min="<?= RecipeForm::SERV_MIN ?>" max="<?= RecipeForm::SERV_MAX ?>" step="1" required
                   data-label="Servings" data-int data-minval="<?= RecipeForm::SERV_MIN ?>" data-maxval="<?= RecipeForm::SERV_MAX ?>">
            <p class="field-error"><?= e($v->first('base_servings')) ?></p>
        </div>
    </div>

    <fieldset class="field ingredients-field">
        <legend>Ingredients</legend>
        <p class="hint">Amount and unit are optional (e.g. "salt, to taste"). Use the unit list so readers can scale and convert.</p>

        <div id="ingredient-rows" data-max="<?= RecipeForm::ING_MAX ?>">
            <?php foreach ($form->rows as $i => $row) { echo ingredient_row((int) $i, $row, $v); } ?>
        </div>
        <p class="field-error" id="ingredients-error"><?= e($v->first('ingredients')) ?></p>

        <button type="button" class="btn btn-sm" id="add-ingredient">+ Add ingredient</button>

        <template id="ingredient-template">
            <?= ingredient_row('__INDEX__', ['quantity' => '', 'unit' => '', 'name' => ''], null) ?>
        </template>
    </fieldset>

    <div class="field">
        <label for="steps">Steps</label>
        <textarea id="steps" name="steps" rows="8" required
                  maxlength="<?= RecipeForm::STEPS_MAX ?>" data-label="Steps"
                  data-min="<?= RecipeForm::STEPS_MIN ?>" data-max="<?= RecipeForm::STEPS_MAX ?>"
                  placeholder="Write one step per line."><?= e($d['steps']) ?></textarea>
        <p class="hint">One step per line. Blank lines are ignored.</p>
        <p class="field-error"><?= e($v->first('steps')) ?></p>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save changes' : 'Share recipe' ?></button>
        <a class="btn" href="<?= $editing ? 'recipe.php?id=' . (int) $id : 'index.php' ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
