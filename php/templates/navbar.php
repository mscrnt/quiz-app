<!-- path: templates/navbar.php -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App</title>
    
    <!-- Local Font Awesome CSS for icons -->
    <link rel="stylesheet" href="/../static/css/all.min.css">
    <!-- Custom CSS for your site -->
    <link rel="stylesheet" href="/../static/css/style.css">
    <!-- Add app.js for global functionality -->
    <script src="/../static/app.js" defer></script>
</head>
<body>

<div class="navbar">
    <a href="/index.php"><i class="fas fa-home"></i> Home</a>

    <?php if (isset($_SESSION['user_name'])): ?>
        <span>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
        <a href="/index.php?change_name=1"><i class="fas fa-edit"></i> Change Name</a>
    <?php endif; ?>

    <div class="dropdown">
        <button class="dropbtn"><i class="fas fa-layer-group"></i> Decks</button>
        <div class="dropdown-content">
            <?php
            $all_decks_result = $conn->query("SELECT * FROM quiz_decks ORDER BY id DESC");
            if (!$all_decks_result) {
                echo "<p>Error fetching decks: " . $conn->error . "</p>";
            } else {
                while ($deck = $all_decks_result->fetch_assoc()) { ?>
                    <a href="/pages/deck.php?deck_id=<?php echo $deck['id']; ?>">
                        <?php echo htmlspecialchars($deck['name']); ?>
                    </a>
                <?php }
            }
            ?>
        </div>
    </div>

    <a href="/pages/create_deck.php"><i class="fas fa-plus"></i> Create Deck</a>
    <a href="/pages/manage_decks.php"><i class="fas fa-tasks"></i> Manage Decks</a>
</div>
</body>
</html>
