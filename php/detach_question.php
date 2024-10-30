<?php
# path: php/detach_question.php

include 'db.php';

$deck_id = $_POST['deck_id'] ?? null;
$question_id = $_POST['question_id'] ?? null;

if ($deck_id && $question_id) {
    $stmt = $conn->prepare("DELETE FROM deck_questions WHERE deck_id = ? AND question_id = ?");
    $stmt->bind_param("ii", $deck_id, $question_id);

    if ($stmt->execute()) {
        error_log("Detached question $question_id from deck $deck_id.");
    } else {
        error_log("Error detaching question: " . $stmt->error);
    }

    $stmt->close();
} else {
    error_log("Error: Deck ID or Question ID is missing.");
}
