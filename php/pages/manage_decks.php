<?php
// path: php/pages/manage_decks.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    error_log("Error: User not logged in.");
    exit("Error: User not logged in.");
}

// Get the latest deck for the user
$newest_deck = getLatestDeckForUser($user_id);
$deck_id = $newest_deck['id'] ?? null;
error_log("Deck ID found: " . ($deck_id ?: 'None') . " for user_id $user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Decks</title>
</head>
<body>
    <?php include __DIR__ . '/../templates/navbar.php'; ?>
    
    <div class="container">
        <?php include __DIR__ . '/../templates/sidebar.php'; ?>
        
        <!-- Deck Details Container -->
        <div class="deck-details-container" id="deck-details-container">
            <div class="card-container">
                <div class="card">
                    <div class="card-front">
                        <?php
                        // Display the edit form for the newest deck or a welcome message if no decks
                        if ($deck_id) {
                            $_GET['deck_id'] = $deck_id; // Set deck_id in GET for edit_deck.php
                            include __DIR__ . '/../templates/edit_deck.php';
                        } else {
                            echo "<h2>Welcome</h2><p>No decks found. Please create a new deck to get started.</p>";
                        }
                        ?>
                    </div>
                    <div class="card-back">
                        <!-- Additional content can be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Manage Decks page loaded.");
            const deckDetailsContainer = document.getElementById("deck-details-container");
            if (deckDetailsContainer) {
                console.log("Deck details container is present.");
            } else {
                console.error("Deck details container is missing.");
            }
        });
    </script>
</body>
</html>

