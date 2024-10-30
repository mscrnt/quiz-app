<?php
# path: php/sidebar_questions.php

include 'db.php';

$deck_id = $_GET['deck_id'] ?? 0;

if ($deck_id) {
    echo "<button onclick=\"showQuestionForm($deck_id)\">Create New Question</button>";
    
    // Query to fetch questions for the selected deck
    $questions_result = $conn->query("SELECT * FROM quiz_questions WHERE id IN (SELECT question_id FROM deck_questions WHERE deck_id = $deck_id)");

    // Check if the query was successful
    if ($questions_result === false) {
        echo "<p>Error fetching deck questions: " . $conn->error . "</p>";
    } else {
        echo "<h2>Questions</h2><ul class='deck-list'>";
        while ($question = $questions_result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($question['question_text']) . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>Invalid or missing deck ID.</p>";
}
?>
