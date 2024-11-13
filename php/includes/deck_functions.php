<?php
# path: includes/deck_functions.php

include_once 'db_functions.php';

/**
 * Creates a new deck.
 */
function createDeck($name, $description, $time_limit, $created_by, $is_private = true, $collection_ids = []) {
    global $conn;

    error_log("Debug - Starting createDeck function with parameters: name=$name, description=$description, time_limit=$time_limit, created_by=$created_by, is_private=$is_private, collection_ids=" . implode(',', $collection_ids));

    $query = "INSERT INTO quiz_decks (name, description, time_limit, created_by, is_private) VALUES (?, ?, ?, ?, ?)";
    $types = "ssiii";
    $params = [$name, $description, $time_limit, $created_by, $is_private];

    $stmt = executePreparedQuery($query, $types, $params);
    if (!$stmt) {
        error_log("Error: Failed to prepare or execute create deck query. " . $conn->error);
        return false;
    }

    $deck_id = $stmt->insert_id;
    error_log("Debug - Created deck with ID: $deck_id");

    // Link deck to the user
    if (!linkUserToDeck($created_by, $deck_id)) {
        error_log("Error: Failed to link user to deck for user ID $created_by and deck ID $deck_id.");
    }

    // Link the deck to each collection
    foreach ($collection_ids as $collection_id) {
        if (!linkDeckToCollection($deck_id, $collection_id)) {
            error_log("Error: Failed to link deck $deck_id to collection $collection_id.");
        }
    }

    return $deck_id;
}

/**
 * Updates deck details including name, description, time limit, and privacy setting.
 *
 * @param int $deck_id The ID of the deck to update.
 * @param string $name The new name for the deck.
 * @param string $description The new description for the deck.
 * @param int $time_limit The time limit in seconds for the deck.
 * @param int $is_private The privacy setting for the deck (1 for private, 0 for public).
 * @return bool True if the update is successful, false otherwise.
 */
function updateDeck($deck_id, $name, $description, $time_limit, $is_private) {
    global $conn;

    // Log incoming data for debugging
    error_log("Updating deck with ID $deck_id: Name - $name, Description - $description, Time Limit - $time_limit, Privacy - $is_private");

    // Update deck details
    $query = "UPDATE quiz_decks SET name = ?, description = ?, time_limit = ?, is_private = ? WHERE id = ?";
    $types = "ssiii";
    $params = [$name, $description, $time_limit, $is_private, $deck_id];

    if (!executePreparedQuery($query, $types, $params)) {
        error_log("Error: Failed to update deck details for deck ID $deck_id.");
        return false;
    }

    error_log("Deck updated successfully for ID $deck_id.");
    return true;
}



/**
 * Creates a new collection and links it to the user.
 */
function createCollection($name, $user_id) {
    $query = "INSERT INTO collections (name, owner_id, created_at) VALUES (?, ?, NOW())";
    $stmt = executePreparedQuery($query, "si", [$name, $user_id]);
    if (!$stmt) return false;

    $collection_id = $stmt->insert_id;
    linkUserToCollection($user_id, $collection_id);
    return $collection_id;
}

/**
 * Links a question to a deck.
 */
function attachQuestionToDeck($deck_id, $question_id) {
    $query = "INSERT INTO deck_questions (deck_id, question_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE deck_id=deck_id";
    executePreparedQuery($query, "ii", [$deck_id, $question_id]);
}

/**
 * Detaches a question from a deck.
 */
function detachQuestionFromDeck($deck_id, $question_id) {
    $query = "DELETE FROM deck_questions WHERE deck_id = ? AND question_id = ?";
    executePreparedQuery($query, "ii", [$deck_id, $question_id]);
}

/**
 * Saves or updates a question for a specific deck.
 */
function saveQuestion($deck_id, $question_text, $question_type, $answers = [], $question_id = null) {
    $user_id = $_SESSION['user_id'] ?? null;

    error_log("saveQuestion called with parameters: deck_id=$deck_id, question_text=$question_text, question_type=$question_type, question_id=" . ($question_id ?? 'null'));

    if (!$user_id || !$deck_id) {
        error_log("Error in saveQuestion: User ID or Deck ID is missing.");
        return false;
    }

    // Insert or Update question
    if ($question_id) {
        error_log("Updating existing question with ID $question_id");
        $query = "UPDATE quiz_questions SET question = ?, question_type = ? WHERE id = ? AND created_by = ?";
        $types = "ssii";
        $params = [$question_text, $question_type, $question_id, $user_id];
    } else {
        error_log("Inserting new question");
        $query = "INSERT INTO quiz_questions (question, question_type, created_by) VALUES (?, ?, ?)";
        $types = "ssi";
        $params = [$question_text, $question_type, $user_id];
    }

    $stmt = executePreparedQuery($query, $types, $params);

    if ($stmt && !$question_id) {
        $question_id = $stmt->insert_id;
        error_log("New question inserted with ID $question_id. Attaching to deck $deck_id.");
        attachQuestionToDeck($deck_id, $question_id);
    }

    // Handle multiple-choice answers
    if ($question_type === 'multiple_choice') {
        error_log("Processing answers for question ID $question_id.");
        $deleteQuery = "DELETE FROM quiz_answers WHERE question_id = ?";
        executePreparedQuery($deleteQuery, "i", [$question_id]);
        saveQuestionAnswers($question_id, $answers);
    }

    error_log("Question saved successfully with ID $question_id.");
    return $question_id;
}


/**
 * Deletes a question from the database.
 */
function deleteQuestion($question_id) {
    $query1 = "DELETE FROM deck_questions WHERE question_id = ?";
    executePreparedQuery($query1, "i", [$question_id]);

    $query2 = "DELETE FROM quiz_questions WHERE id = ?";
    executePreparedQuery($query2, "i", [$question_id]);
}

/**
 * Fetches questions assigned to a specific deck.
 */
function getQuestions($deck_id) {
    $query = "SELECT q.id, q.question FROM quiz_questions q 
              JOIN deck_questions dq ON q.id = dq.question_id 
              WHERE dq.deck_id = ?";
    $result = fetchPreparedQuery($query, "i", [$deck_id]);

    if (!$result) {
        error_log("Error executing query for deck ID: $deck_id");
        return []; // Return an empty array on error
    }

    $questions = $result->fetch_all(MYSQLI_ASSOC);

    if (count($questions) > 0) {
        error_log("Questions found for deck ID: $deck_id - Total: " . count($questions));
    } else {
        error_log("No questions found for deck ID: $deck_id");
    }

    return $questions;
}



/**
 * Fetches all answers for a question.
 */
function getAnswersByQuestionId($question_id) {
    $query = "SELECT * FROM quiz_answers WHERE question_id = ?";
    $result = fetchPreparedQuery($query, "i", [$question_id]);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Saves answers for a multiple-choice question.
 */
function saveQuestionAnswers($question_id, $answers) {
    foreach ($answers as $answer) {
        $query = "INSERT INTO quiz_answers (question_id, answer, is_correct) VALUES (?, ?, ?)";
        $types = "isi";
        $params = [$question_id, $answer['text'], isset($answer['is_correct']) ? 1 : 0];
        executePreparedQuery($query, $types, $params);
    }
}

/**
 * Creates or retrieves a collection and links it to a user and deck.
 */
function createOrUpdateCollection($name, $user_id) {
    $query = "INSERT INTO collections (name, owner_id, created_at) VALUES (?, ?, NOW())";
    $stmt = executePreparedQuery($query, "si", [$name, $user_id]);
    if (!$stmt) return false;

    $collection_id = $stmt->insert_id;
    linkUserToCollection($user_id, $collection_id);
    return $collection_id;
}

/**
 * Links a user to a collection.
 */
function linkUserToCollection($user_id, $collection_id) {
    $query = "INSERT INTO user_collections (user_id, collection_id) VALUES (?, ?)";
    executePreparedQuery($query, "ii", [$user_id, $collection_id]);
}

/**
 * Links a deck to a user.
 */
function linkUserToDeck($user_id, $deck_id) {
    $query = "INSERT INTO user_decks (user_id, deck_id) VALUES (?, ?)";
    executePreparedQuery($query, "ii", [$user_id, $deck_id]);
}

/**
 * Links a deck to a collection.
 */
function linkDeckToCollection($deck_id, $collection_id) {
    $query = "INSERT INTO collection_decks (deck_id, collection_id) VALUES (?, ?)";
    executePreparedQuery($query, "ii", [$deck_id, $collection_id]);
}

/**
 * Deletes a deck if private or removes association if public.
 */
function deleteDeck($user_id, $deck_id) {
    global $conn;

    // Check if the deck exists and if it is private
    $query = "SELECT is_private FROM quiz_decks WHERE id = ?";
    $stmt = executePreparedQuery($query, "i", [$deck_id]);
    $deck = $stmt->get_result()->fetch_assoc();

    if (!$deck) {
        error_log("Error: Deck with ID $deck_id not found in the database."); // Log the missing deck ID
        return false;
    }

    if ($deck['is_private']) {
        // If private, delete the deck from quiz_decks and user_decks
        $delete_deck_query = "DELETE FROM quiz_decks WHERE id = ?";
        executePreparedQuery($delete_deck_query, "i", [$deck_id]);

        $delete_user_association = "DELETE FROM user_decks WHERE deck_id = ?";
        executePreparedQuery($delete_user_association, "i", [$deck_id]);

        error_log("Deck with ID $deck_id was deleted successfully as it was private."); // Log successful deletion
    } else {
        // If public, only delete association in user_decks
        $delete_association_query = "DELETE FROM user_decks WHERE user_id = ? AND deck_id = ?";
        executePreparedQuery($delete_association_query, "ii", [$user_id, $deck_id]);

        error_log("Association for deck ID $deck_id was deleted for user ID $user_id."); // Log successful dissociation
    }

    return true;
}


/**
 * Retrieves question details by question ID.
 */
function getQuestionById($question_id) {
    $query = "SELECT * FROM quiz_questions WHERE id = ?";
    $result = fetchPreparedQuery($query, "i", [$question_id]);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Fetches all decks linked to a specific user in descending order by deck ID.
 */
function getDecksByUser($user_id) {
    $query = "
        SELECT d.*, 
               (SELECT COUNT(*) FROM deck_questions WHERE deck_id = d.id) AS question_count 
        FROM quiz_decks d 
        JOIN user_decks ud ON d.id = ud.deck_id 
        WHERE ud.user_id = ?
        ORDER BY d.id DESC  -- Sort by deck ID in descending order
    ";
    $result = fetchPreparedQuery($query, "i", [$user_id]);
    $decks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    error_log("Fetched " . count($decks) . " decks for user_id $user_id.");
    return $decks;
}


/**
 * Fetches all collections linked to a specific user.
 */
function getCollectionsByUser($user_id) {
    $query = "
        SELECT c.*, 
               (SELECT COUNT(*) 
                FROM collection_decks cd 
                JOIN user_decks ud ON cd.deck_id = ud.deck_id 
                WHERE cd.collection_id = c.id AND ud.user_id = ?) AS deck_count 
        FROM collections c 
        JOIN user_collections uc ON c.id = uc.collection_id 
        WHERE uc.user_id = ?
    ";
    $result = fetchPreparedQuery($query, "ii", [$user_id, $user_id]);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Fetches the latest deck for a user by creation date.
 *
 * @param int $user_id The ID of the user.
 * @return array|null An associative array of the latest deck details or null if no deck found.
 */
function getLatestDeckForUser($user_id) {
    $query = "
        SELECT d.*, 
               (SELECT COUNT(*) FROM deck_questions WHERE deck_id = d.id) AS question_count
        FROM quiz_decks d
        JOIN user_decks ud ON d.id = ud.deck_id
        WHERE ud.user_id = ?
        ORDER BY d.created_at DESC 
        LIMIT 1
    ";

    $result = fetchPreparedQuery($query, 'i', [$user_id]);
    if ($result && $result->num_rows > 0) {
        $deck = $result->fetch_assoc();
        error_log("Fetched latest deck ID for user $user_id: " . $deck['id']); // Log fetched deck ID
        return $deck;
    } else {
        error_log("No decks found for user_id: $user_id");
        return null;
    }
}


?>
