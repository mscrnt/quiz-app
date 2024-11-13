<?php
// path: php/templates/get_questions.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

header('Content-Type: application/json'); // Set the response type to JSON

// Check if deck_id is provided
$deck_id = $_GET['deck_id'] ?? null;
if (!$deck_id) {
    http_response_code(400); // Bad request if deck_id is missing
    echo json_encode(['error' => 'Deck ID is required']);
    exit;
}

// Fetch questions for the provided deck_id
$questions = getQuestions($deck_id);

// Check if questions were found and return the data as JSON
if ($questions) {
    echo json_encode($questions);
} else {
    echo json_encode([]); // Return an empty array if no questions are found
}
