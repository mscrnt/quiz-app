<?php
# path: php/edit_deck.php

include 'db.php';

$deck_id = isset($_GET['deck_id']) ? intval($_GET['deck_id']) : 0;
if (!$deck_id) die("No valid deck ID provided.");

$deck_result = $conn->query("SELECT * FROM quiz_decks WHERE id = $deck_id");
$deck = $deck_result->fetch_assoc();
$deck_name = htmlspecialchars($deck['name']);
$time_limit_seconds = $deck['time_limit'] ?? 0;

$time_limit_minutes = intdiv($time_limit_seconds, 60);
$time_limit_remaining_seconds = $time_limit_seconds % 60;

$questions_result = $conn->query("SELECT * FROM quiz_questions WHERE deck_id = $deck_id");
?>

<div class="edit-deck-container">
    <button onclick="window.location.href='manage_decks.php'">Back to Decks</button>
    <h1>Edit Deck: <?php echo htmlspecialchars($deck_name); ?></h1>
    <form method="POST" action="update_deck.php?deck_id=<?php echo $deck_id; ?>">
        <label for="deck_name">Deck Name:</label>
        <input type="text" id="deck_name" name="deck_name" value="<?php echo htmlspecialchars($deck_name); ?>" required>
        
        <label for="time_limit">Time Limit:</label>
        <input type="number" id="time_limit_minutes" name="time_limit_minutes" value="<?php echo $time_limit_minutes; ?>" placeholder="Minutes" min="0">
        <input type="number" id="time_limit_seconds" name="time_limit_seconds" value="<?php echo $time_limit_remaining_seconds; ?>" placeholder="Seconds" min="0" max="59">

        <div id="questions">
            <?php while ($question = $questions_result->fetch_assoc()) : ?>
                <div class="question">
                    <label>Question:</label>
                    <input type="text" name="questions[]" value="<?php echo htmlspecialchars($question['question']); ?>" required>
                    <input type="hidden" name="question_ids[]" value="<?php echo $question['id']; ?>">
                </div>
            <?php endwhile; ?>
        </div>

        <button type="submit">Save Changes</button>
    </form>
</div>
