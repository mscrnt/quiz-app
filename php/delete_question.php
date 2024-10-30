<?php
# path: php/delete_question.php

include 'db.php';

$question_id = $_POST['question_id'] ?? null;

if ($question_id) {
    // Remove question from all decks before deleting
    $conn->query("DELETE FROM deck_questions WHERE question_id = $question_id");

    // Delete the question itself
    $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ?");
    $stmt->bind_param("i", $question_id);
    
    if ($stmt->execute()) {
        error_log("Deleted question with ID $question_id.");
    } else {
        error_log("Error deleting question: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    error_log("Error: Question ID is missing.");
}
