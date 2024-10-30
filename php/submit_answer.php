<?php
# Path: php/submit_answer.php

session_start();
$conn = new mysqli(getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    die("Error: No user ID found in session. Please start the quiz again.");
}

// Get POST data
$deck_id = $_POST['deck_id'];
$question_id = $_POST['question_id'];
$selected_answer = $_POST['answer'];  // The user's selected answer ID
$current_question = (int)$_POST['current_question'];
$total_questions = (int)$_POST['total_questions'];

// Verify that question_id is valid
if (empty($question_id) || !is_numeric($question_id)) {
    die("Error: Invalid question ID.");
}

// Fetch the correct answer ID for comparison
$correct_answer_query = "SELECT id FROM quiz_answers WHERE question_id = $question_id AND is_correct = 1";
$correct_answer_result = $conn->query($correct_answer_query);
if ($correct_answer_result && $correct_answer_result->num_rows > 0) {
    $correct_answer_row = $correct_answer_result->fetch_assoc();
    $correct_answer_id = $correct_answer_row['id'];
} else {
    die("Error fetching correct answer: " . $conn->error . " | Query: $correct_answer_query");
}

// Initialize score in session if not set
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 0;
}

// Compare the selected answer ID with the correct answer ID
if ($selected_answer == $correct_answer_id) {
    $_SESSION['score'] += 1;  // Increment score if correct
}

// Redirect to the next question or to the results page if the quiz is complete
if ($current_question < $total_questions) {
    header("Location: deck.php?deck_id=$deck_id&question=" . ($current_question + 1));
    exit;
} else {
    // Store the final score in the database using a prepared statement
    $user_id = $_SESSION['user_id'];  // Use the user_id from the session
    $score = $_SESSION['score'];

    $stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id, deck_id, score, attempted_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("iii", $user_id, $deck_id, $score);
        if ($stmt->execute()) {
            error_log("Quiz completed. Final score stored in DB: $score");
        } else {
            error_log("Error executing statement: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement: " . $conn->error);
    }

    // Reset the session score for the next attempt
    unset($_SESSION['score']);

    // Redirect to the quiz completion page
    header("Location: deck.php?deck_id=$deck_id&question=completed");
    exit;
}
?>
