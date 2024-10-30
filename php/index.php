<?php
# path: php/index.php

include 'db.php';
include 'navbar.php';

// Check if user name is set in session
if (isset($_POST['user_name']) && !empty($_POST['user_name'])) {
    $user_name = $_POST['user_name'];

    // Check if the user already exists in the 'users' table
    $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name) VALUES (?)");
        $stmt->bind_param("s", $user_name);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
        }
    }

    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $user_name;

    $stmt->close();
}

// Check if a deck exists in the database
$deck_result = $conn->query("SELECT * FROM quiz_decks ORDER BY id DESC LIMIT 1");

// Check if query was successful
if ($deck_result) {
    $recent_deck = $deck_result->fetch_assoc();
} else {
    echo "<p>Error fetching recent deck: " . $conn->error . "</p>";
    $recent_deck = null;
}

// Define the front and back content for the card
$front_content = '<h2>Welcome to the Quiz App</h2>';

if (!isset($_SESSION['user_name'])) {
    $front_content .= '
        <form method="POST">
            <label for="user_name">Enter your name:</label>
            <input type="text" id="user_name" name="user_name" required>
            <input type="submit" value="Submit">
        </form>';
} else {
    $front_content .= '<h3>Hello, ' . htmlspecialchars($_SESSION['user_name']) . '!</h3>';
    $front_content .= '<button onclick="flipCard()">Flip Card</button>';

    if ($recent_deck) {
        $front_content .= '
            <h4>You can open your latest deck: ' . htmlspecialchars($recent_deck['name']) . '</h4>
            <button onclick="window.location.href=\'manage_decks.php?deck_id=' . $recent_deck['id'] . '\'">Open Latest Deck</button>';
    } else {
        $front_content .= '
            <h4>No decks available. Please create a deck.</h4>
            <button onclick="window.location.href=\'manage_decks.php\'">Create Deck</button>';
    }
}

$back_content = '<h3>This is the back of the card!</h3>';
$back_content .= '<button onclick="flipCard()">Flip Back</button>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App</title>
    <link rel="stylesheet" href="static/style.css">
</head>
<body>
    <?php include 'card.php'; ?>
</body>
</html>
