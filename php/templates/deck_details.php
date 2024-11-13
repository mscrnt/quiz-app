<?php
// path: php/templates/deck_details.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session if not already active
}

$user_id = $_SESSION['user_id'] ?? null;
$deck_id = $_GET['deck_id'] ?? null;

// Debugging: Check session and GET parameters
error_log("Debug - User ID: " . ($user_id ?? 'N/A') . ", Deck ID (from GET): " . ($deck_id ?? 'N/A'));

if (!$user_id || !$deck_id) {
    error_log("Error: User not logged in or deck ID not provided. User ID: " . ($user_id ?? 'N/A') . ", Deck ID: " . ($deck_id ?? 'N/A'));
    exit("Error: Access denied.");
}

// Retrieve deck details securely
$deck_details_query = "
    SELECT d.*, 
           (SELECT COUNT(*) FROM deck_questions WHERE deck_id = d.id) AS question_count
    FROM quiz_decks d
    JOIN user_decks ud ON d.id = ud.deck_id
    WHERE d.id = ? AND ud.user_id = ?
";
$deck_result = fetchPreparedQuery($deck_details_query, 'ii', [$deck_id, $user_id]);

if (!$deck_result || $deck_result->num_rows === 0) {
    error_log("Error: No deck found or access denied for deck ID $deck_id.");
    echo "<p>No deck found or you do not have access to this deck.</p>";
    return;
}

$deck = $deck_result->fetch_assoc();
$deck_name = htmlspecialchars($deck['name']);
$description = htmlspecialchars($deck['description']);
$created_at = htmlspecialchars($deck['created_at']);
$time_limit = $deck['time_limit'] ?? 0;
$time_limit_minutes = intdiv($time_limit, 60);
$time_limit_seconds = $time_limit % 60;
$question_count = $deck['question_count'];

// Log the deck information for debugging
error_log("Deck Details Loaded: " . print_r($deck, true));

// Display deck details
echo "<h2>{$deck_name}</h2>";
echo "<p><strong>Created At:</strong> {$created_at}</p>";
echo "<p><strong>Description:</strong> {$description}</p>";
echo "<p><strong>Time Limit:</strong> {$time_limit_minutes}m {$time_limit_seconds}s</p>";
echo "<p><strong>Number of Questions:</strong> {$question_count}</p>";
?>
