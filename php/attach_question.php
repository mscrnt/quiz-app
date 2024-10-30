<?php
# path: php/attach_question.php

include 'db.php';

$deck_id = $_POST['deck_id'] ?? null;
$question_id = $_POST['question_id'] ?? null;

if ($deck_id && $question_id) {
    $stmt = $conn->prepare("INSERT INTO deck_questions (deck_id, question_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE deck_id=deck_id");
    if ($stmt) {
        $stmt->bind_param("ii", $deck_id, $question_id);
        if ($stmt->execute()) {
            error_log("Successfully linked question $question_id to deck $deck_id.");
        } else {
            error_log("Error executing statement: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement: " . $conn->error);
    }
} else {
    error_log("Deck ID or Question ID is missing.");
}
