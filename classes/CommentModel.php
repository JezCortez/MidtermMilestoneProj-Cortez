<?php
declare(strict_types=1);

final class CommentModel
{
    public function __construct(private PDO $pdo) {}

    /** Oldest first, so a conversation reads top to bottom. */
    public function forRecipe(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.user_id, c.body, c.created_at, c.edited_at, u.name AS author
               FROM comments c
               INNER JOIN users u ON u.id = c.user_id
              WHERE c.recipe_id = :rid
              ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute([':rid' => $recipeId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.recipe_id, c.user_id, c.body, r.title AS recipe_title
               FROM comments c
               INNER JOIN recipes r ON r.id = c.recipe_id
              WHERE c.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $recipeId, int $userId, string $body): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (recipe_id, user_id, body) VALUES (:rid, :uid, :body)'
        );
        $stmt->execute([':rid' => $recipeId, ':uid' => $userId, ':body' => $body]);
    }

    /** Owner-only (user_id is in the WHERE). Marks the comment as edited. */
    public function update(int $id, int $userId, string $body): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE comments SET body = :body, edited_at = NOW() WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([':body' => $body, ':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() === 1;
    }

    /** Owner-only. */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        return $stmt->rowCount() === 1;
    }
}
