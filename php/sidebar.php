<?php
# path: php/sidebar.php

include 'db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    error_log("Error: User not logged in.");
    exit;
}

// Fetch all decks linked to the user
$my_decks = $conn->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM deck_questions WHERE deck_id = d.id) AS question_count
    FROM quiz_decks d
    INNER JOIN user_decks ud ON d.id = ud.deck_id
    WHERE ud.user_id = $user_id
    ORDER BY d.created_at DESC
");

// Fetch collections linked to the user
$collections_result = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM collection_decks WHERE collection_id = c.id) AS deck_count
    FROM collections c
    INNER JOIN user_collections uc ON c.id = uc.collection_id
    WHERE uc.user_id = $user_id
    ORDER BY c.created_at DESC
");
?>

<div class="sidebar" id="sidebar">
    <h2>Manage Your Quizzes</h2>
    <div class="button-section">
        <button id="create-deck-button" onclick="showCreateDeckForm()">Create New Deck</button>
    </div>

    <div class="sidebar-section">
        <h3>My Decks</h3>
        <div id="my-decks">
            <?php if ($my_decks && $my_decks->num_rows > 0): ?>
                <ul>
                    <?php while ($deck = $my_decks->fetch_assoc()) : ?>
                        <li>
                            <a href="#" class="deck-link" data-deck-id="<?php echo $deck['id']; ?>">
                                <?php echo htmlspecialchars($deck['name']); ?> (<?php echo $deck['question_count']; ?>)
                            </a>
                            <ul id="questions-<?php echo $deck['id']; ?>" class="questions-list" style="display: none;">
                                <li><a href="#" class="add-question-link" data-deck-id="<?php echo $deck['id']; ?>" style="font-style: italic;">Add Question +</a></li>
                            </ul>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No decks found. Start by creating a new deck or adding one from public collections.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar-section">
        <h3>My Collections</h3>
        <div id="collections">
            <?php if ($collections_result && $collections_result->num_rows > 0): ?>
                <ul>
                    <?php while ($collection = $collections_result->fetch_assoc()) : ?>
                        <li>
                            <h4 class="collection-toggle" data-collection-id="<?php echo $collection['id']; ?>">
                                <?php echo htmlspecialchars($collection['name']); ?> (<?php echo $collection['deck_count']; ?>)
                            </h4>
                            <ul id="collection-<?php echo $collection['id']; ?>" class="deck-list" style="display: none;">
                                <?php
                                $decks_in_collection = $conn->query("
                                    SELECT d.*, 
                                           (SELECT COUNT(*) FROM deck_questions WHERE deck_id = d.id) AS question_count
                                    FROM quiz_decks d
                                    INNER JOIN collection_decks cd ON d.id = cd.deck_id
                                    WHERE cd.collection_id = {$collection['id']}
                                    ORDER BY d.created_at DESC
                                ");
                                while ($deck = $decks_in_collection->fetch_assoc()) : ?>
                                    <li>
                                        <a href="#" class="deck-link" data-deck-id="<?php echo $deck['id']; ?>">
                                            <?php echo htmlspecialchars($deck['name']); ?> (<?php echo $deck['question_count']; ?>)
                                        </a>
                                        <ul id="questions-<?php echo $deck['id']; ?>" class="questions-list" style="display: none;">
                                            <li><a href="#" class="add-question-link" data-deck-id="<?php echo $deck['id']; ?>" style="font-style: italic;">Add Question +</a></li>
                                        </ul>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No collections found. Start by creating a new collection or adding a public one.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
