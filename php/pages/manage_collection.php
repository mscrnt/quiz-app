<?php
// path: pages/manage_collection.php
include_once __DIR__ . '/../includes/db_functions.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$deck_id = $data['deck_id'] ?? 0;
$collection_id = $data['collection_id'] ?? 0;

if (!$user_id || !$deck_id || !$collection_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid data.']);
    exit;
}

if ($action === "add") {
    $query = "INSERT INTO collection_decks (deck_id, collection_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE deck_id = deck_id";
    $success = executePreparedQuery($query, "ii", [$deck_id, $collection_id]);
} elseif ($action === "remove") {
    $query = "DELETE FROM collection_decks WHERE deck_id = ? AND collection_id = ?";
    $success = executePreparedQuery($query, "ii", [$deck_id, $collection_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    exit;
}

echo json_encode(['success' => $success]);
?>
