<?php
// path: pages/create_collection.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$data = json_decode(file_get_contents("php://input"), true);
$collection_name = trim($data['name'] ?? '');
$deck_id = $data['deck_id'] ?? 0;

if (!$user_id || !$deck_id || empty($collection_name)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data.']);
    exit;
}

// Use the createCollection function to create the collection and link it to the user
$collection_id = createCollection($collection_name, $user_id);

if ($collection_id) {
    // Associate this new collection with the deck
    $associate_query = "INSERT INTO collection_decks (deck_id, collection_id) VALUES (?, ?)";
    $associate_success = executePreparedQuery($associate_query, "ii", [$deck_id, $collection_id]);
    
    echo json_encode(['success' => $associate_success, 'collection_id' => $collection_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create collection.']);
}
exit;
?>
