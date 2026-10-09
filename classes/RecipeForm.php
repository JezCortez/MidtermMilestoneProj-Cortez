<?php
declare(strict_types=1);

/**
 * Reads and validates the "share / edit a recipe" form (server side).
 * The same rules are mirrored in the browser by assets/js/validation.js.
 */
final class RecipeForm
{
    public const TITLE_MIN = 3;    public const TITLE_MAX = 100;
    public const DESC_MIN  = 10;   public const DESC_MAX  = 300;
    public const STEPS_MIN = 10;   public const STEPS_MAX = 5000;
    public const SERV_MIN  = 1;    public const SERV_MAX  = 50;
    public const ING_MAX   = 30;   public const ING_NAME_MAX = 100;
    public const QTY_MAX   = 99999;

    public array $data = [
        'title' => '', 'description' => '', 'category_id' => 0, 'base_servings' => 4, 'steps' => '',
    ];

    /** Rows exactly as submitted (used to refill the form after an error). */
    public array $rows = [['quantity' => '', 'unit' => '', 'name' => '']];

    /** Cleaned rows ready to save: [quantity|null, unit|null, name] */
    public array $ingredients = [];

    public Validator $v;

    public function __construct(private CategoryModel $categories)
    {
        $this->v = new Validator();
    }

    public function handle(array $post): void
    {
        $title = trim((string) ($post['title'] ?? ''));
        $descr = trim((string) ($post['description'] ?? ''));
        $cat   = filter_var($post['category_id'] ?? null, FILTER_VALIDATE_INT);
        $serv  = filter_var($post['base_servings'] ?? null, FILTER_VALIDATE_INT);

        // steps: one per line, blank lines removed
        $lines = preg_split('/\R/', (string) ($post['steps'] ?? '')) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
        $steps = implode("\n", $lines);

        $this->data = [
            'title'         => $title,
            'description'   => $descr,
            'category_id'   => $cat === false ? 0 : $cat,
            'base_servings' => $serv === false ? 0 : $serv,
            'steps'         => $steps,
        ];

        $this->v->text('title', $title, 'Title', self::TITLE_MIN, self::TITLE_MAX);
        $this->v->text('description', $descr, 'Description', self::DESC_MIN, self::DESC_MAX);

        if ($cat === false || !$this->categories->exists($cat)) {
            $this->v->add('category_id', 'Choose a category.');
        }
        if ($serv === false || $serv < self::SERV_MIN || $serv > self::SERV_MAX) {
            $this->v->add('base_servings', 'Servings must be a whole number from ' . self::SERV_MIN . ' to ' . self::SERV_MAX . '.');
        }

        $this->v->text('steps', $steps, 'Steps', self::STEPS_MIN, self::STEPS_MAX);
        if (count($lines) > 40) {
            $this->v->add('steps', 'Please keep it to 40 steps or fewer.');
        }

        $this->readIngredients($post['ingredients'] ?? []);
    }

    private function readIngredients(mixed $submitted): void
    {
        $this->rows = [];
        $this->ingredients = [];

        if (!is_array($submitted)) {
            $submitted = [];
        }
        $submitted = array_slice(array_values($submitted), 0, self::ING_MAX + 1);

        foreach ($submitted as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $qtyRaw = trim((string) ($row['quantity'] ?? ''));
            $unit   = trim((string) ($row['unit'] ?? ''));
            $name   = trim((string) ($row['name'] ?? ''));

            $this->rows[$i] = ['quantity' => $qtyRaw, 'unit' => $unit, 'name' => $name];

            // completely empty rows are simply ignored
            if ($qtyRaw === '' && $unit === '' && $name === '') {
                continue;
            }

            $qty = null;
            if ($qtyRaw !== '') {
                if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0 || (float) $qtyRaw > self::QTY_MAX) {
                    $this->v->add("ing_{$i}_quantity", 'Enter an amount above 0.');
                } else {
                    $qty = round((float) $qtyRaw, 3);
                }
            }

            if ($unit !== '' && !Units::isValid($unit)) {
                $this->v->add("ing_{$i}_unit", 'Choose a unit from the list.');
            }

            $this->v->text("ing_{$i}_name", $name, 'Ingredient name', 1, self::ING_NAME_MAX);

            $this->ingredients[] = [
                'quantity' => $qty,
                'unit'     => $unit === '' ? null : $unit,
                'name'     => $name,
            ];
        }

        if ($this->rows === []) {
            $this->rows[] = ['quantity' => '', 'unit' => '', 'name' => ''];
        }

        if (count($submitted) > self::ING_MAX) {
            $this->v->add('ingredients', 'Please keep it to ' . self::ING_MAX . ' ingredients or fewer.');
        }
        if ($this->ingredients === []) {
            $this->v->add('ingredients', 'Add at least one ingredient.');
        }
    }
}
