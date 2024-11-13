<?php
// path: templates/edit_deck.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;
$deck_id = isset($_GET['deck_id']) ? intval($_GET['deck_id']) : 0;

// Handle DELETE request for deck deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    header('Content-Type: application/json');

    if (!$deck_id || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid deck ID or user not logged in.']);
        exit;
    }

    // Delete the current deck
    $delete_success = deleteDeck($user_id, $deck_id);

    // First, look for the next youngest deck (higher ID) for the user
    $next_youngest_query = "
        SELECT d.id 
        FROM quiz_decks d
        JOIN user_decks ud ON d.id = ud.deck_id
        WHERE ud.user_id = ? AND d.id > ?
        ORDER BY d.id ASC
        LIMIT 1
    ";
    $next_youngest_result = fetchPreparedQuery($next_youngest_query, "ii", [$user_id, $deck_id]);
    $next_youngest_deck = $next_youngest_result->fetch_assoc();

    if ($next_youngest_deck) {
        $next_deck_id = $next_youngest_deck['id'];
    } else {
        // If no younger deck exists, look for the next oldest deck (lower ID)
        $next_oldest_query = "
            SELECT d.id 
            FROM quiz_decks d
            JOIN user_decks ud ON d.id = ud.deck_id
            WHERE ud.user_id = ? AND d.id < ?
            ORDER BY d.id DESC
            LIMIT 1
        ";
        $next_oldest_result = fetchPreparedQuery($next_oldest_query, "ii", [$user_id, $deck_id]);
        $next_oldest_deck = $next_oldest_result->fetch_assoc();
        $next_deck_id = $next_oldest_deck['id'] ?? null;
    }

    echo json_encode(['success' => $delete_success, 'next_deck_id' => $next_deck_id]);
    exit;
}

// Handle POST request for updating deck details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$deck_id || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid deck ID or user not logged in.']);
        exit;
    }

    // Capture and log POST data for debugging
    $deck_name = $_POST['deck_name'] ?? '';
    $description = $_POST['deck_description'] ?? '';
    $minutes = isset($_POST['time_limit_minutes']) && is_numeric($_POST['time_limit_minutes']) ? (int)$_POST['time_limit_minutes'] : 0;
    $seconds = isset($_POST['time_limit_seconds']) && is_numeric($_POST['time_limit_seconds']) ? (int)$_POST['time_limit_seconds'] : 0;
    $time_limit = ($minutes * 60) + $seconds;

    // Log received data for troubleshooting
    error_log("Received data for deck update - ID: $deck_id, Name: $deck_name, Description: $description, Time Limit: $time_limit, Privacy: " . (isset($_POST['is_public']) ? $_POST['is_public'] : 'N/A'));

    // Ensure privacy setting is correct and once public cannot revert
    $deck_result = $conn->query("SELECT is_private FROM quiz_decks WHERE id = $deck_id");
    $deck = $deck_result->fetch_assoc();
    $is_private = isset($_POST['is_public']) && $_POST['is_public'] == '1' ? 0 : $deck['is_private'];

    // Update deck details
    $update_success = updateDeck($deck_id, $deck_name, $description, $time_limit, $is_private);
    error_log("Deck updated successfully: " . json_encode(['success' => $update_success]));

    echo json_encode(['success' => $update_success]);
    exit;
}

// For GET requests, load deck details for editing
if (!$deck_id) {
    echo json_encode(['success' => false, 'error' => 'No valid deck ID provided.']);
    exit;
}

// Fetch the deck from the database
$deck_result = $conn->query("SELECT * FROM quiz_decks WHERE id = $deck_id");
if (!$deck_result || $deck_result->num_rows === 0) {
    $next_deck = getLatestDeckForUser($user_id);
    if ($next_deck) {
        header("Location: edit_deck.php?deck_id=" . $next_deck['id']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Deck not found and no other decks are available.']);
    }
    exit;
}
$deck = $deck_result->fetch_assoc();

$deck_name = htmlspecialchars($deck['name']);
$deck_description = htmlspecialchars($deck['description']);
$time_limit_seconds = $deck['time_limit'] ?? 0;
$is_public = !$deck['is_private'];

$time_limit_minutes = intdiv($time_limit_seconds, 60);
$time_limit_remaining_seconds = $time_limit_seconds % 60;
?>

<div class="edit-deck-container">
    <div id="flashMessage_<?php echo $deck_id; ?>" class="flash-message">SAVED!</div>

    <h1><i class="fas fa-edit"></i> Edit Deck: <?php echo $deck_name; ?></h1>
    
    <form id="editDeckForm_<?php echo $deck_id; ?>" method="POST">
        <div class="form-group">
            <label for="deck_name_<?php echo $deck_id; ?>">Deck Name:</label>
            <input type="text" id="deck_name_<?php echo $deck_id; ?>" name="deck_name" value="<?php echo $deck_name; ?>" required>
        </div>

        <div class="form-group">
            <label for="deck_description_<?php echo $deck_id; ?>">Description:</label>
            <textarea id="deck_description_<?php echo $deck_id; ?>" name="deck_description"><?php echo $deck_description; ?></textarea>
        </div>

        <div class="form-group">
            <label for="time_limit_minutes_<?php echo $deck_id; ?>">Time Limit:</label>
            <div class="time-inputs">
                <input type="number" id="time_limit_minutes_<?php echo $deck_id; ?>" name="time_limit_minutes" value="<?php echo $time_limit_minutes; ?>" placeholder="Minutes" min="0">
                <input type="number" id="time_limit_seconds_<?php echo $deck_id; ?>" name="time_limit_seconds" value="<?php echo $time_limit_remaining_seconds; ?>" placeholder="Seconds" min="0" max="59">
            </div>
        </div>

        <div class="form-group">
            <label for="is_public_<?php echo $deck_id; ?>">
                Make Deck Public
                <span class="tooltip" title="Once made public, this cannot be undone.">ℹ️</span>
            </label>
            <input type="checkbox" id="is_public_<?php echo $deck_id; ?>" name="is_public" value="1" <?php echo $is_public ? 'checked disabled' : ''; ?>>
        </div>

        <h3>Questions</h3>
        <div id="questions_<?php echo $deck_id; ?>">
            <!-- Placeholder for questions (loaded separately) -->
        </div>

        <div class="button-container">
            <button type="button" id="addQuestionButton_<?php echo $deck_id; ?>" class="btn-add" onclick="loadQuestionForm(<?php echo $deck_id; ?>)">
                <i class="fas fa-plus"></i> Add Question
            </button>
            <button type="button" id="saveChangesButton_<?php echo $deck_id; ?>" class="btn-save" onclick="saveChanges(<?php echo $deck_id; ?>)">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <button type="button" id="deleteDeckButton_<?php echo $deck_id; ?>" class="btn-delete" onclick="deleteDeck(<?php echo $deck_id; ?>)">
                <i class="fas fa-trash"></i> Delete Deck
            </button>
        </div>
    </form>
</div>
