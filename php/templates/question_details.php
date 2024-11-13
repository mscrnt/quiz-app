<?php
// path: php/templates/question_details.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

session_start();
$question_id = $_GET['question_id'] ?? 0;
$deck_id = $_GET['deck_id'] ?? 0;

if (!$question_id || !$deck_id) {
    echo "<p>Question not found or missing deck ID.</p>";
    exit;
}

// Fetch question details
$question = getQuestionById($question_id);
if (!$question) {
    echo "<p>Question not found.</p>";
    exit;
}

echo "<h2>" . htmlspecialchars($question['question']) . "</h2>";

// Display answer options if it's multiple-choice
if ($question['question_type'] === 'multiple_choice') {
    $answers = getAnswersByQuestionId($question_id);
    
    if (!empty($answers)) {
        echo "<ul>";
        foreach ($answers as $answer) {
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

// Edit and delete buttons
echo "<button onclick=\"loadEditQuestionForm({$deck_id}, {$question_id})\">Edit Question</button>";
echo "<button onclick=\"deleteQuestion({$question_id})\">Delete Question</button>";
?>
