<?php
// path: pages/search_collections.php

include_once __DIR__ . '/../includes/db_functions.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit;
}

// Get the search query from the URL
$query = $_GET['query'] ?? '';
$query = trim($query);

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['success' => false, 'collections' => []]);
    exit;
}

// Fetch collections matching the query for the logged-in user
$sql = "
    SELECT id, name 
    FROM collections 
    WHERE owner_id = ? AND name LIKE CONCAT('%', ?, '%')
    ORDER BY name ASC
";
$result = fetchPreparedQuery($sql, "is", [$user_id, $query]);

$collections = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $collections[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name'])
        ];
    }
}

echo json_encode(['success' => true, 'collections' => $collections]);
exit;
