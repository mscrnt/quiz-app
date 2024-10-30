<!-- php/navbar.php -->

<div class="navbar">
    <a href="index.php">Home</a>

    <?php if (isset($_SESSION['user_name'])): ?>
        <span>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
        <a href="index.php?change_name=1">Change Name</a>
    <?php endif; ?>

    <div class="dropdown">
        <button class="dropbtn">Decks</button>
        <div class="dropdown-content">
            <?php
            // Attempt to retrieve all decks
            $all_decks_result = $conn->query("SELECT * FROM quiz_decks ORDER BY id DESC");

            // Check if the query was successful
            if (!$all_decks_result) {
                echo "<p>Error fetching decks: " . $conn->error . "</p>";
            } else {
                // Fetch and display each deck if query was successful
                while ($deck = $all_decks_result->fetch_assoc()) { ?>
                    <a href="deck.php?deck_id=<?php echo $deck['id']; ?>">
                        <?php echo htmlspecialchars($deck['name']); ?>
                    </a>
                <?php }
            }
            ?>
        </div>
    </div>

    <a href="create_deck.php">Create Deck</a>
    <a href="manage_decks.php">Manage Decks</a>
</div>

<!-- Include JavaScript files here -->
<script src="static/sidebar.js"></script>
<script src="static/script.js"></script>
