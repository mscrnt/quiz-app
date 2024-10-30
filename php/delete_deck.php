<?php
# path: php/delete_deck.php

session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$deck_id = $_GET['deck_id'] ?? 0;

if ($user_id && $deck_id) {
    // Remove the deck association for the user, effectively hiding it
    $stmt = $conn->prepare("DELETE FROM user_decks WHERE user_id = ? AND deck_id = ?");
    $stmt->bind_param("ii", $user_id, $deck_id);

    if ($stmt->execute()) {
        error_log("Removed deck $deck_id from user $user_id's account.");
    } else {
        error_log("Error removing deck: " . $stmt->error);
    }

    $stmt->close();

    // Redirect back to manage_decks.php with a success message
    header("Location: manage_decks.php?status=removed");
    exit;
} else {
    die("Error: User ID or Deck ID is missing.");
}
