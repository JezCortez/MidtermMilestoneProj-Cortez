<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

// Only allow logged-in users
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if ($query === '' || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search for distinct ingredient names that match the query
    $stmt = $pdo->prepare(
        'SELECT DISTINCT name 
         FROM ingredients 
         WHERE name LIKE :query 
         ORDER BY name 
         LIMIT 10'
    );
    $stmt->execute([':query' => $query . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
