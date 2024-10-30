<?php
// path: php/manage_decks.php
session_start();
include 'db.php';
include 'navbar.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    error_log("Error: User not logged in.");
    exit("Error: User not logged in.");
}

// Fetch the newest deck for the user, if available
$newest_deck = null;
$newest_deck_result = $conn->query("
    SELECT d.*
    FROM quiz_decks d
    JOIN user_decks ud ON d.id = ud.deck_id
    WHERE ud.user_id = $user_id
    ORDER BY d.created_at DESC LIMIT 1
");
if ($newest_deck_result && $newest_deck_result->num_rows > 0) {
    $newest_deck = $newest_deck_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Decks</title>
    <link rel="stylesheet" href="static/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <!-- Deck Details Container -->
        <div class="deck-details-container" id="deck-details-container">
            <div class="card-container">
                <div class="card">
                    <div class="card-front">
                        <?php
                        // Display the newest deck details if available
                        if ($newest_deck) {
                            $deck_id = $newest_deck['id'];
                            include 'deck_details.php';
                        } else {
                            echo "<h2>Welcome</h2><p>Select a deck or add a question to start.</p>";
                        }
                        ?>
                    </div>
                    <div class="card-back">
                        <!-- Question form will load here dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
