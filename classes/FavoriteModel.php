<?php
declare(strict_types=1);

final class FavoriteModel
{
    public function __construct(private PDO $pdo) {}

    /** Idempotent: saving twice does nothing (composite primary key + INSERT IGNORE). */
    public function add(int $userId, int $recipeId): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO favorites (user_id, recipe_id) VALUES (:uid, :rid)');
        $stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
    }

    public function remove(int $userId, int $recipeId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM favorites WHERE user_id = :uid AND recipe_id = :rid');
        $stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
    }

    public function countFor(int $recipeId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM favorites WHERE recipe_id = :rid');
        $stmt->execute([':rid' => $recipeId]);
        return (int) $stmt->fetchColumn();
    }
}
