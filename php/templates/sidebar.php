<?php
// path: php/templates/sidebar.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

$baseurl = getenv('BASE_URL') ?: 'http://localhost/';

session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    error_log("Error: User not logged in.");
    exit("Error: User not logged in.");
}

// Fetch decks and collections with their decks for the logged-in user
$my_decks = getDecksByUser($user_id); // This now returns decks in descending order
$collections = getCollectionsByUser($user_id);

// Log the retrieval status
error_log("Sidebar loaded for user_id $user_id with " . count($my_decks) . " decks and " . count($collections) . " collections.");
?>

<div class="sidebar" id="sidebar">
    <h2>Manage Your Quizzes</h2>
    <div class="button-section">
        <button id="create-deck-button"><i class="fas fa-plus-circle"></i> Create New Deck</button>
    </div>

    <!-- My Decks Section with collapsible toggle -->
    <div class="sidebar-section">
        <h3>
            <i class="fas fa-caret-down toggle-icon"></i> My Decks
        </h3>
        <div id="my-decks" class="collapsible-section" style="display: block;">
            <?php if ($my_decks): ?>
                <ul class="deck-list">
                    <?php foreach ($my_decks as $deck): ?>
                        <li class="deck-item">
                            <a href="#" class="deck-link" data-deck-id="<?php echo $deck['id']; ?>">
                                <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($deck['name']); ?> (<?php echo $deck['question_count'] ?? '0'; ?>)
                            </a>
                            <ul id="questions-<?php echo $deck['id']; ?>" class="questions-list" style="display: none;"></ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No decks found. Start by creating a new deck.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Collections Section with collapsible toggle -->
    <div class="sidebar-section">
        <h3>
            <i class="fas fa-caret-down toggle-icon"></i> My Collections
        </h3>
        <div id="collections" class="collapsible-section" style="display: block;">
            <?php if ($collections): ?>
                <ul class="collection-list">
                    <?php foreach ($collections as $collection): ?>
                        <li class="collection-item">
                            <h4 class="collection-title" data-collection-id="<?php echo $collection['id']; ?>">
                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($collection['name']); ?>
                            </h4>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No collections found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
