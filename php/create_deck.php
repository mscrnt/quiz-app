<?php
# path: php/create_deck.php

ob_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deck_name = $_POST['deck_name'];
    $description = $_POST['description'] ?? '';
    $time_limit = ($_POST['time_limit_minutes'] * 60) + $_POST['time_limit_seconds'];
    $new_collection_name = $_POST['new_collection'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        error_log("Error: User not logged in.");
        echo "Error: User not logged in.";
        exit;
    }

    // Insert a new collection if needed
    if (!empty($new_collection_name)) {
        $stmt = $conn->prepare("INSERT INTO collections (name, owner_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $new_collection_name, $user_id);
        $stmt->execute();
        $collection_id = $stmt->insert_id;
        $stmt->close();

        // Link the new collection to the user
        $stmt_user_col = $conn->prepare("INSERT INTO user_collections (user_id, collection_id) VALUES (?, ?)");
        $stmt_user_col->bind_param("ii", $user_id, $collection_id);
        $stmt_user_col->execute();
        $stmt_user_col->close();
    }

    // Insert the new deck
    $stmt = $conn->prepare("INSERT INTO quiz_decks (name, description, time_limit, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $deck_name, $description, $time_limit, $user_id);
    $stmt->execute();
    $deck_id = $stmt->insert_id;
    $stmt->close();

    // Link the new deck to the user
    $stmt_user_deck = $conn->prepare("INSERT INTO user_decks (user_id, deck_id) VALUES (?, ?)");
    $stmt_user_deck->bind_param("ii", $user_id, $deck_id);
    $stmt_user_deck->execute();
    $stmt_user_deck->close();

    // Add selected collections for the deck if specified
    if (isset($_POST['collection_ids'])) {
        foreach ($_POST['collection_ids'] as $collection_id) {
            $stmt_col_deck = $conn->prepare("INSERT INTO collection_decks (collection_id, deck_id) VALUES (?, ?)");
            $stmt_col_deck->bind_param("ii", $collection_id, $deck_id);
            $stmt_col_deck->execute();
            $stmt_col_deck->close();
        }
    }

    // Redirect back to manage_decks.php
    header("Location: manage_decks.php");
    exit;
}
