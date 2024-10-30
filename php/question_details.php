<?php
# path: php/question_details.php

include 'db.php';

$question_id = $_GET['question_id'] ?? 0;
$deck_id = $_GET['deck_id'] ?? 0;

if (!$question_id || !$deck_id) {
    echo "<p>Question not found or missing deck ID.</p>";
    exit;
}

// Query for question details
$question_result = $conn->query("SELECT * FROM quiz_questions WHERE id = $question_id");
$question = $question_result->fetch_assoc();

if (!$question) {
    echo "<p>Question not found.</p>";
    exit;
}

echo "<h2>" . htmlspecialchars($question['question']) . "</h2>";

// Display answer options if multiple-choice
if ($question['question_type'] == 'multiple_choice') {
    $answers_result = $conn->query("SELECT * FROM quiz_answers WHERE question_id = $question_id");
    
    if ($answers_result && $answers_result->num_rows > 0) {
        echo "<ul>";
        while ($answer = $answers_result->fetch_assoc()) {
            $answer_text = htmlspecialchars($answer['answer']);
            $is_correct = $answer['is_correct'] ? " (Correct)" : "";
            echo "<li>{$answer_text}{$is_correct}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No answer options available.</p>";
    }
} else {
    echo "<p>Open-ended question. No answer options available.</p>";
}

// Change the button to call loadEditQuestionForm function
echo "<button onclick=\"loadEditQuestionForm({$deck_id}, {$question_id})\">Edit Question</button>";
