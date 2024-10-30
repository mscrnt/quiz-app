<?php
// path: php/deck_details.php

include 'db.php';

$deck_id = $_GET['deck_id'] ?? $deck_id ?? 0; // Use $deck_id from manage_decks.php if available
$user_id = $_SESSION['user_id'] ?? null;

$result = $conn->query("
    SELECT d.*,
           (SELECT COUNT(*) FROM deck_questions WHERE deck_id = d.id) AS question_count
    FROM quiz_decks d
    JOIN user_decks ud ON d.id = ud.deck_id
    WHERE d.id = $deck_id AND ud.user_id = $user_id
");
$deck = $result->fetch_assoc();

if ($deck) {
    $deck_name = htmlspecialchars($deck['name']);
    $description = htmlspecialchars($deck['description']);
    $created_at = htmlspecialchars($deck['created_at']);
    $time_limit = $deck['time_limit'] ?? 0;
    $time_limit_minutes = intdiv($time_limit, 60);
    $time_limit_seconds = $time_limit % 60;
    $question_count = $deck['question_count'];

    echo "<h2>{$deck_name}</h2>";
    echo "<p><strong>Created At:</strong> {$created_at}</p>";
    echo "<p><strong>Description:</strong> {$description}</p>";
    echo "<p><strong>Time Limit:</strong> {$time_limit_minutes}m {$time_limit_seconds}s</p>";
    echo "<p><strong>Questions:</strong> {$question_count}</p>";
} else {
    echo "<p>No deck found or you do not have access to this deck.</p>";
}
?>
