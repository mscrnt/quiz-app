<?php
# Path: php/deck.php

session_start();
$conn = new mysqli(getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));

if ($conn->connect_error) {
    echo "<script>console.error('Connection failed: " . $conn->connect_error . "');</script>";
    die("Connection failed: " . $conn->connect_error);
}

// Get deck_id from URL or fallback to recent deck
$deck_id = isset($_GET['deck_id']) ? $_GET['deck_id'] : 0;

// Fetch deck details
$deck_result = $conn->query("SELECT * FROM quiz_decks WHERE id = $deck_id");
if (!$deck_result) {
    echo "<script>console.error('Error fetching deck details: " . $conn->error . "');</script>";
    die("Error fetching deck details: " . $conn->error);
}
$deck = $deck_result->fetch_assoc();
echo "<script>console.log('Deck ID: $deck_id, Deck: " . $deck['name'] . "');</script>";

if (!$deck) {
    die("Deck not found.");
}

// Fetch total number of questions
$question_result = $conn->query("SELECT COUNT(*) as total FROM quiz_questions WHERE deck_id = $deck_id");
if (!$question_result) {
    echo "<script>console.error('Error fetching total questions: " . $conn->error . "');</script>";
    die("Error fetching total questions: " . $conn->error);
}
$total_questions = $question_result->fetch_assoc()['total'];
echo "<script>console.log('Total Questions: $total_questions');</script>";

// Get the current question number (or default to 1 if starting)
$current_question = isset($_GET['question']) ? $_GET['question'] : 1;

// Check if quiz is completed
if ($current_question === 'completed') {
    // Fetch the final score from the database instead of using the session
    $user_id = $_SESSION['user_id']; // Ensure user_id is set in the session
    $score_result = $conn->query("SELECT score FROM quiz_attempts WHERE user_id = $user_id AND deck_id = $deck_id ORDER BY attempted_at DESC LIMIT 1");

    if ($score_result && $score_result->num_rows > 0) {
        $score_row = $score_result->fetch_assoc();
        $final_score = $score_row['score'];
    } else {
        $final_score = 0; // Default score if there's no entry
    }

    echo "<script>console.log('Quiz completed. Final score: $final_score');</script>";

    echo "<h1>Quiz Completed!</h1>";
    echo "<h2>Deck: " . $deck['name'] . "</h2>";
    echo "<h3>Your final score: $final_score</h3>";
    echo "<button onclick=\"window.location.href='index.php'\">Go Back to Home</button>";
    exit;
}
// Validate question number
$current_question = (int)$current_question;
echo "<script>console.log('Current Question Number: $current_question');</script>";

if ($current_question > $total_questions || $current_question < 1) {
    echo "<script>console.error('Invalid question number: $current_question');</script>";
    die("Invalid question number: $current_question");
}

// Fetch current question data
$question_data_result = $conn->query("SELECT * FROM quiz_questions WHERE deck_id = $deck_id LIMIT " . ($current_question - 1) . ", 1");
if (!$question_data_result) {
    echo "<script>console.error('Error fetching question data: " . $conn->error . "');</script>";
    die("Error fetching question data: " . $conn->error);
}
$question_data = $question_data_result->fetch_assoc();

if (!$question_data) {
    die("Question not found.");
}
echo "<script>console.log('Question ID: " . $question_data['id'] . ", Question Text: " . $question_data['question'] . "');</script>";

// Fetch the possible answers for the current question
$answer_data_result = $conn->query("SELECT * FROM quiz_answers WHERE question_id = " . $question_data['id']);
if (!$answer_data_result) {
    echo "<script>console.error('Error fetching answers: " . $conn->error . "');</script>";
    die("Error fetching answers: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deck: <?php echo htmlspecialchars($deck['name']); ?></title>
    <link rel="stylesheet" href="static/style.css">
</head>
<body>
    <div class="container">
        <?php if ($current_question <= $total_questions): ?>
            <!-- Show the quiz question -->
            <h1>Question <?php echo $current_question; ?> of <?php echo $total_questions; ?></h1>
            <h2><?php echo htmlspecialchars($question_data['question']); ?></h2>

            <!-- Answer Form -->
            <form action="submit_answer.php" method="POST">
                <input type="hidden" name="deck_id" value="<?php echo $deck_id; ?>">
                <input type="hidden" name="question_id" value="<?php echo $question_data['id']; ?>">
                <input type="hidden" name="current_question" value="<?php echo $current_question; ?>">
                <input type="hidden" name="total_questions" value="<?php echo $total_questions; ?>">

                <div class="answers">
                    <?php while ($answer_data = $answer_data_result->fetch_assoc()) { ?>
                        <label>
                            <input type="radio" name="answer" value="<?php echo $answer_data['id']; ?>" required>
                            <?php echo htmlspecialchars($answer_data['answer']); ?>
                        </label><br>
                    <?php } ?>
                </div>

                <input type="submit" value="Submit Answer">
            </form>

        <?php else: ?>
            <!-- Quiz completion -->
            <h1>Quiz Completed!</h1>
            <h2>Deck: <?php echo htmlspecialchars($deck['name']); ?></h2>
            <h3>Your score will be shown soon</h3>

            <!-- Restart or go back -->
            <button onclick="window.location.href='deck.php?deck_id=<?php echo $deck_id; ?>&question=1'">Restart Quiz</button>
            <button onclick="window.location.href='index.php'">Go Back to Home</button>
        <?php endif; ?>
    </div>
</body>
</html>
