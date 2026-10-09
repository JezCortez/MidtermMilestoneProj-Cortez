<?php
declare(strict_types=1);

final class RecipeModel
{
    public function __construct(private PDO $pdo) {}

    /* ------------------------------------------------------------
     * Browsing: latest first, keyword search, category filter
     * ------------------------------------------------------------ */

    /**
     * @return array{rows: array, total: int}
     */
    public function search(
        int $viewerId,
        string $keyword = '',
        ?int $categoryId = null,
        bool $mineOnly = false,
        int $page = 1,
        int $perPage = 9
    ): array {
        $where  = [];
        $params = [];

        // Every word must appear in the title, the description or an ingredient name.
        $words = preg_split('/\s+/', trim($keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach (array_slice($words, 0, 5) as $i => $word) {
            // escape LIKE wildcards typed by the user (| is our escape character)
            $like = '%' . str_replace(['|', '%', '_'], ['||', '|%', '|_'], $word) . '%';
            $where[] = "(r.title LIKE :w{$i}a ESCAPE '|'
                      OR r.description LIKE :w{$i}b ESCAPE '|'
                      OR EXISTS (SELECT 1 FROM ingredients i
                                  WHERE i.recipe_id = r.id AND i.name LIKE :w{$i}c ESCAPE '|'))";
            $params[":w{$i}a"] = $like;
            $params[":w{$i}b"] = $like;
            $params[":w{$i}c"] = $like;
        }

        if ($categoryId !== null) {
            $where[] = 'r.category_id = :cat';
            $params[':cat'] = $categoryId;
        }
        if ($mineOnly) {
            $where[] = 'r.user_id = :mine';
            $params[':mine'] = $viewerId;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // total (for pagination)
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM recipes r $whereSql");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $rows = $this->fetchCards(
            $whereSql, $params, $viewerId, '', 'r.created_at DESC, r.id DESC',
            $perPage, max(0, ($page - 1) * $perPage)
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /** Recipes the member saved, most recently saved first. */
    public function favoritesOf(int $userId): array
    {
        return $this->fetchCards(
            '', [':fvuser' => $userId], $userId,
            'INNER JOIN favorites fv ON fv.recipe_id = r.id AND fv.user_id = :fvuser',
            'fv.created_at DESC, r.id DESC',
            100, 0
        );
    }

    /** Shared SELECT for recipe cards (author, category, counts, "did I save it?"). */
    private function fetchCards(
        string $whereSql,
        array $params,
        int $viewerId,
        string $extraJoin,
        string $orderBy,
        int $limit,
        int $offset
    ): array {
        $sql = "SELECT r.id, r.user_id, r.title, r.description, r.created_at, r.edited_at,
                       u.name AS author, c.name AS category,
                       (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS fav_count,
                       (SELECT COUNT(*) FROM comments cm WHERE cm.recipe_id = r.id) AS comment_count,
                       EXISTS (SELECT 1 FROM favorites f2
                                WHERE f2.recipe_id = r.id AND f2.user_id = :me) AS is_fav
                  FROM recipes r
                  INNER JOIN users u      ON u.id = r.user_id
                  INNER JOIN categories c ON c.id = r.category_id
                  $extraJoin
                  $whereSql
                 ORDER BY $orderBy
                 LIMIT :lim OFFSET :off";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':me', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /* ------------------------------------------------------------
     * Single recipe
     * ------------------------------------------------------------ */

    public function find(int $id, int $viewerId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.user_id, r.category_id, r.title, r.description, r.steps,
                    r.base_servings, r.created_at, r.edited_at,
                    u.name AS author, c.name AS category,
                    (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS fav_count,
                    EXISTS (SELECT 1 FROM favorites f2
                             WHERE f2.recipe_id = r.id AND f2.user_id = :me) AS is_fav
               FROM recipes r
               INNER JOIN users u      ON u.id = r.user_id
               INNER JOIN categories c ON c.id = r.category_id
              WHERE r.id = :id'
        );
        $stmt->execute([':me' => $viewerId, ':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM recipes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function ingredients(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT quantity, unit, name FROM ingredients WHERE recipe_id = :rid ORDER BY position, id'
        );
        $stmt->execute([':rid' => $recipeId]);
        return $stmt->fetchAll();
    }

    /* ------------------------------------------------------------
     * Write operations (transactions keep recipe + ingredients together)
     * ------------------------------------------------------------ */

    /** @param array $data  title, description, category_id, base_servings, steps
     *  @param array $ingredients  list of [quantity, unit, name] */
    public function create(int $userId, array $data, array $ingredients): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO recipes (user_id, category_id, title, description, steps, base_servings)
                 VALUES (:uid, :cat, :title, :descr, :steps, :serv)'
            );
            $stmt->execute([
                ':uid'   => $userId,
                ':cat'   => $data['category_id'],
                ':title' => $data['title'],
                ':descr' => $data['description'],
                ':steps' => $data['steps'],
                ':serv'  => $data['base_servings'],
            ]);
            $id = (int) $this->pdo->lastInsertId();

            $this->insertIngredients($id, $ingredients);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Owner-only update. Sets edited_at. Returns false if the recipe is not the user's. */
    public function update(int $id, int $userId, array $data, array $ingredients): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE recipes
                    SET category_id = :cat, title = :title, description = :descr,
                        steps = :steps, base_servings = :serv, edited_at = NOW()
                  WHERE id = :id AND user_id = :uid'
            );
            $stmt->execute([
                ':cat'   => $data['category_id'],
                ':title' => $data['title'],
                ':descr' => $data['description'],
                ':steps' => $data['steps'],
                ':serv'  => $data['base_servings'],
                ':id'    => $id,
                ':uid'   => $userId,
            ]);

            if ($stmt->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            // simplest correct approach: replace the whole ingredient list
            $del = $this->pdo->prepare('DELETE FROM ingredients WHERE recipe_id = :rid');
            $del->execute([':rid' => $id]);
            $this->insertIngredients($id, $ingredients);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Owner-only. Ingredients, comments and favorites go with it (ON DELETE CASCADE). */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM recipes WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() === 1;
    }

    private function insertIngredients(int $recipeId, array $ingredients): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ingredients (recipe_id, position, quantity, unit, name)
             VALUES (:rid, :pos, :qty, :unit, :name)'
        );
        foreach (array_values($ingredients) as $pos => $ing) {
            $stmt->execute([
                ':rid'  => $recipeId,
                ':pos'  => $pos,
                ':qty'  => $ing['quantity'],
                ':unit' => $ing['unit'],
                ':name' => $ing['name'],
            ]);
        }
    }

    /**
     * Did the member actually change anything? (Used so that saving without
     * changes does not add the "Edited" tag.)
     */
    public static function isSame(array $oldRecipe, array $oldIngredients, array $data, array $ingredients): bool
    {
        $a = [
            (int) $oldRecipe['category_id'], $oldRecipe['title'], $oldRecipe['description'],
            $oldRecipe['steps'], (int) $oldRecipe['base_servings'],
            array_map(fn($i) => self::normalize($i), $oldIngredients),
        ];
        $b = [
            (int) $data['category_id'], $data['title'], $data['description'],
            $data['steps'], (int) $data['base_servings'],
            array_map(fn($i) => self::normalize($i), $ingredients),
        ];
        return $a === $b;
    }

    private static function normalize(array $ing): array
    {
        return [
            $ing['quantity'] === null ? null : round((float) $ing['quantity'], 3),
            $ing['unit'] ?: null,
            $ing['name'],
        ];
    }
}
