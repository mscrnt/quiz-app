<?php
# path: php/get_questions.php

include 'db.php';

$deck_id = $_GET['deck_id'] ?? 0;
if (!$deck_id) {
    error_log("No deck ID provided.");
    echo "<li>No questions assigned to this deck.</li>";
    exit;
}

error_log("Fetching questions for deck ID: $deck_id");

$deck_id = (int) $deck_id; // Casting to integer

// Start with the "Add Question +" link
echo "<li><a href='#' class='add-question-link' data-deck-id='" . $deck_id . "' style='font-style: italic;'>Add Question +</a></li>";

// Query for questions
$questions = $conn->query("SELECT q.* FROM quiz_questions q 
                           JOIN deck_questions dq ON q.id = dq.question_id 
                           WHERE dq.deck_id = $deck_id");

if ($questions && $questions->num_rows > 0) {
    error_log("Found " . $questions->num_rows . " questions for deck ID $deck_id");
    while ($question = $questions->fetch_assoc()) {
        echo "<li><a href='#' class='question-link' data-question-id='" . $question['id'] . "'>" . htmlspecialchars($question['question']) . "</a></li>";
    }
} else {
    error_log("No questions found for deck ID $deck_id or query failed: " . $conn->error);
    echo "<li>No questions assigned to this deck.</li>";
}
?>
