<?php
# Path: php/results.php

session_start();
$conn = new mysqli(getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get deck_id from URL
$deck_id = $_GET['deck_id'];

// Fetch the deck name
$deck_result = $conn->query("SELECT * FROM quiz_decks WHERE id = $deck_id");
$deck = $deck_result->fetch_assoc();

// Get the total number of questions
$total_questions_result = $conn->query("SELECT COUNT(*) as total FROM quiz_questions WHERE deck_id = $deck_id");
$total_questions = $total_questions_result->fetch_assoc()['total'];

// Get the user's score
$score = isset($_SESSION['score']) ? $_SESSION['score'] : 0;

// Store the quiz attempt in the database
$user_id = $_SESSION['user_id'];
$conn->query("INSERT INTO quiz_attempts (user_id, deck_id, score) VALUES ($user_id, $deck_id, $score)");

// Clear session score after storing the results
unset($_SESSION['score']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link rel="stylesheet" href="static/style.css">
</head>
<body>
    <div class="container">
        <h1>Quiz Completed!</h1>
        <h2>Deck: <?php echo $deck['name']; ?></h2>
        <h3>Your Score: <?php echo $score; ?> out of <?php echo $total_questions; ?></h3>

        <!-- Options to restart or go back -->
        <button onclick="window.location.href='deck.php?deck_id=<?php echo $deck_id; ?>'">Restart Quiz</button>
        <button onclick="window.location.href='index.php'">Go Back to Home</button>
    </div>
</body>
</html>
