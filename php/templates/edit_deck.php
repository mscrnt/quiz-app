<?php
// path: templates/edit_deck.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;
$deck_id = isset($_GET['deck_id']) ? intval($_GET['deck_id']) : 0;

// Handle DELETE request for deck deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    header('Content-Type: application/json');

    if (!$deck_id || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid deck ID or user not logged in.']);
        exit;
    }

    $delete_success = deleteDeck($user_id, $deck_id);

    $next_youngest_query = "
        SELECT d.id 
        FROM quiz_decks d
        JOIN user_decks ud ON d.id = ud.deck_id
        WHERE ud.user_id = ? AND d.id > ?
        ORDER BY d.id ASC
        LIMIT 1
    ";
    $next_youngest_result = fetchPreparedQuery($next_youngest_query, "ii", [$user_id, $deck_id]);
    $next_youngest_deck = $next_youngest_result->fetch_assoc();

    if ($next_youngest_deck) {
        $next_deck_id = $next_youngest_deck['id'];
    } else {
        $next_oldest_query = "
            SELECT d.id 
            FROM quiz_decks d
            JOIN user_decks ud ON d.id = ud.deck_id
            WHERE ud.user_id = ? AND d.id < ?
            ORDER BY d.id DESC
            LIMIT 1
        ";
        $next_oldest_result = fetchPreparedQuery($next_oldest_query, "ii", [$user_id, $deck_id]);
        $next_oldest_deck = $next_oldest_result->fetch_assoc();
        $next_deck_id = $next_oldest_deck['id'] ?? null;
    }

    echo json_encode(['success' => $delete_success, 'next_deck_id' => $next_deck_id]);
    exit;
}

// Handle POST request for updating deck details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$deck_id || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid deck ID or user not logged in.']);
        exit;
    }

    $deck_name = substr($_POST['deck_name'] ?? '', 0, 25);
    $description = $_POST['deck_description'] ?? '';
    $minutes = isset($_POST['time_limit_minutes']) && is_numeric($_POST['time_limit_minutes']) ? (int)$_POST['time_limit_minutes'] : 0;
    $seconds = isset($_POST['time_limit_seconds']) && is_numeric($_POST['time_limit_seconds']) ? (int)$_POST['time_limit_seconds'] : 0;
    $time_limit = ($minutes * 60) + $seconds;

    $deck_result = $conn->query("SELECT is_private FROM quiz_decks WHERE id = $deck_id");
    $deck = $deck_result->fetch_assoc();
    $is_private = isset($_POST['is_public']) && $_POST['is_public'] == '1' ? 0 : $deck['is_private'];

    $update_success = updateDeck($deck_id, $deck_name, $description, $time_limit, $is_private);
    echo json_encode(['success' => $update_success]);
    exit;
}

// Load deck details for editing
if (!$deck_id) {
    echo json_encode(['success' => false, 'error' => 'No valid deck ID provided.']);
    exit;
}

$deck_result = $conn->query("SELECT * FROM quiz_decks WHERE id = $deck_id");
if (!$deck_result || $deck_result->num_rows === 0) {
    $next_deck = getLatestDeckForUser($user_id);
    if ($next_deck) {
        header("Location: edit_deck.php?deck_id=" . $next_deck['id']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Deck not found and no other decks are available.']);
    }
    exit;
}
$deck = $deck_result->fetch_assoc();

$deck_name = htmlspecialchars($deck['name']);
$deck_description = htmlspecialchars($deck['description']);
$time_limit_seconds = $deck['time_limit'] ?? 0;
$is_public = !$deck['is_private'];

$time_limit_minutes = intdiv($time_limit_seconds, 60);
$time_limit_remaining_seconds = $time_limit_seconds % 60;

// Fetch question count for the deck
$question_count_query = "
    SELECT COUNT(*) as question_count 
    FROM deck_questions 
    WHERE deck_id = ?
";
$question_count_result = fetchPreparedQuery($question_count_query, "i", [$deck_id]);
$question_count = $question_count_result->fetch_assoc()['question_count'];
?>

<div class="edit-deck-container">
    <div id="flashMessage_<?php echo $deck_id; ?>" class="flash-message">SAVED!</div>

    <h1><i class="fas fa-edit"></i> Edit Deck: <?php echo $deck_name; ?></h1>
    
    <form id="editDeckForm_<?php echo $deck_id; ?>" method="POST">
        <div class="form-columns">
            <!-- Left Column -->
            <div class="left-column">
                <div class="form-group">
                    <label for="deck_name_<?php echo $deck_id; ?>">Deck Name:</label>
                    <input type="text" id="deck_name_<?php echo $deck_id; ?>" name="deck_name" value="<?php echo $deck_name; ?>" maxlength="25" required>
                </div>

                <div class="form-group">
                    <label for="time_limit_minutes_<?php echo $deck_id; ?>">Time Limit:</label>
                    <div class="time-inputs">
                        <input type="number" id="time_limit_minutes_<?php echo $deck_id; ?>" name="time_limit_minutes" value="<?php echo $time_limit_minutes; ?>" placeholder="Minutes" min="0" max="9999" style="width: 7ch;">
                        <input type="number" id="time_limit_seconds_<?php echo $deck_id; ?>" name="time_limit_seconds" value="<?php echo $time_limit_remaining_seconds; ?>" placeholder="Seconds" min="0" max="9999" style="width: 7ch;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Total Questions:</label>
                    <span id="total_questions"><?php echo $question_count ?? '0'; ?></span>
                </div>

                <div class="form-group">
                    <label for="is_public_<?php echo $deck_id; ?>">Make Deck Public</label>
                    <input type="checkbox" id="is_public_<?php echo $deck_id; ?>" name="is_public" value="1" <?php echo $is_public ? 'checked disabled' : ''; ?>>
                    <p class="subtext">Once made public, this cannot be undone.</p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <div class="form-group description-group">
                    <label for="deck_description_<?php echo $deck_id; ?>">Description:</label>
                    <textarea id="deck_description_<?php echo $deck_id; ?>" name="deck_description" style="resize: none; height: 70%;" readonly onclick="openDescriptionModal()"><?php echo $deck_description; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="collection_search_<?php echo $deck_id; ?>">Add to Collections:</label>
                    <div class="search-container">
                        <input type="text" id="collection_search_<?php echo $deck_id; ?>" placeholder="Type to search collections..." maxlength="25">
                        <span id="clear_search_<?php echo $deck_id; ?>" class="clear-btn">&times;</span>
                    </div>
                    <div id="collection_suggestions_<?php echo $deck_id; ?>" class="suggestions-box"></div>
                    <div class="current-edit-deck-collections-container">
                        <ul id="current_collections_<?php echo $deck_id; ?>" class="edit-deck-collection-list">
                            <!-- Populated by JavaScript -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="button-container">
            <button type="button" id="editQuestionsButton_<?php echo $deck_id; ?>" class="btn-edit" onclick="editQuestions(<?php echo $deck_id; ?>)">
                <i class="fas fa-question"></i> Edit Questions
            </button>
            <button type="button" id="saveChangesButton_<?php echo $deck_id; ?>" class="btn-save" onclick="saveChanges(<?php echo $deck_id; ?>)">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <button type="button" id="deleteDeckButton_<?php echo $deck_id; ?>" class="btn-delete" onclick="deleteDeck(<?php echo $deck_id; ?>)">
                <i class="fas fa-trash"></i> Delete Deck
            </button>
        </div>
    </form>
</div>

<!-- Modal for Editing Description -->
<div id="descriptionModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDescriptionModal()">&times;</span>
        <h2>Edit Description</h2>
        <textarea id="modalDescription" style="width: 100%; height: 200px;"><?php echo $deck_description; ?></textarea>
        <button type="button" onclick="saveDescription()">Save</button>
    </div>
</div>

<style>
/* Search Container */
.search-container {
    position: relative;
    display: inline-block;
    width: 100%;
}

#collection_search_<?php echo $deck_id; ?> {
    width: calc(100% - 30px); /* Leave space for the clear button */
    padding-right: 25px;
}

/* Clear Button */
.clear-btn {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-weight: bold;
    color: #888;
    display: none;
}

.clear-btn:hover {
    color: #333;
}

/* Suggestions Box */
.suggestions-box {
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 5px;
    max-height: 100px;
    overflow-y: auto;
    position: absolute;
    background-color: white;
    z-index: 10;
    width: 315px;
    display: none;
    top: 195px;
}

.suggestion-item {
    padding: 5px;
    cursor: pointer;
}

.suggestion-item:hover {
    background-color: #f0f0f0;
}

/* Collection Container for Current Collections */
.current-edit-deck-collections-container {
    max-height: 100px;
    overflow-y: auto;
    padding-right: 10px;
    box-sizing: border-box;
    /* border: 1px solid #ddd; */
    border-radius: 4px;
    /* background-color: #fafafa; */
    margin-top: 10px;
    height: 100px;
    max-width: 345px;
    scrollbar-width: thin;
}

/* Collection List and Item Styling */
.edit-deck-collection-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 5px;
    margin-top: auto;
}

.edit-deck-collection-item {
    /* background-color: #e0e0e0; */
    border-radius: 15px;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-left: 10px;
    border: ridge;
}

.edit-deck-collection-item .remove-btn {
    margin-left: 7px;
    cursor: pointer;
    color: red;
    font-weight: bold;
    padding-right: 10px;
}

/* General Layout and Button Container */
.edit-deck-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.form-columns {
    display: flex;
    width: 675px;
    gap: 20px;
}

.left-column, .right-column {
    flex: 1;
    min-width: max-content;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
}

.form-group .subtext {
    font-size: 0.8em;
    color: gray;
    margin-top: 2px;
}

.time-inputs {
    display: block;
}

.time-inputs input {
    width: 7ch;
}

/* Button Styling */
.button-container {
    display: flex;
    justify-content: space-between;
}

.button-container button {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Specific Button Colors */
.btn-save {
    background-color: green;
    color: white;
}

.btn-edit {
    background-color: blue;
    color: white;
}

.btn-delete {
    background-color: red;
    color: white;
}

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    border-radius: 8px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
}

</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const collectionSearchInput = document.getElementById("collection_search_<?php echo $deck_id; ?>");
    const suggestionsBox = document.getElementById("collection_suggestions_<?php echo $deck_id; ?>");
    const collectionList = document.getElementById("current_collections_<?php echo $deck_id; ?>");
    const addedCollections = new Set();
    const clearSearchBtn = document.getElementById("clear_search_<?php echo $deck_id; ?>");

    if (!collectionSearchInput || !suggestionsBox || !collectionList) {
        console.error("Search input, suggestions box, or collection list not found.");
        return;
    }

    // Input event listener for search box
    collectionSearchInput.addEventListener("input", () => {
        const query = collectionSearchInput.value.trim();
        if (query.length >= 2) {
            fetchCollections(query);
            clearSearchBtn.style.display = "inline"; // Show clear button
        } else {
            suggestionsBox.style.display = "none"; // Hide suggestions
            clearSearchBtn.style.display = "none"; // Hide clear button
        }
    });

    // Clear button event listener
    clearSearchBtn.addEventListener("click", () => {
        collectionSearchInput.value = ""; // Clear input value
        clearSearchBtn.style.display = "none"; // Hide clear button
        suggestionsBox.style.display = "none"; // Hide suggestions
    });

    // Fetch collections based on query
    function fetchCollections(query) {
        console.log(`Searching for collections with query: "${query}"`);

        fetch(`/pages/search_collections.php?query=${encodeURIComponent(query)}`)
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    console.error("Error fetching collections:", data.error);
                    suggestionsBox.innerHTML = `<div class="suggestion-item">Error fetching collections</div>`;
                    suggestionsBox.style.display = "block";
                    return;
                }

                suggestionsBox.innerHTML = ""; // Clear previous suggestions
                const collections = data.collections;

                collections.forEach((collection) => {
                    if (addedCollections.has(collection.name)) return; // Skip already added collections

                    const suggestion = document.createElement("div");
                    suggestion.textContent = collection.name;
                    suggestion.classList.add("suggestion-item");
                    suggestion.addEventListener("click", () => {
                        addCollectionToDeck(collection.id, collection.name);
                        suggestionsBox.style.display = "none"; // Hide suggestions on selection
                    });
                    suggestionsBox.appendChild(suggestion);
                });

                // Add "Create Collection" option if no exact match is found
                if (!collections.some((c) => c.name.toLowerCase() === query.toLowerCase())) {
                    const createOption = document.createElement("div");
                    createOption.textContent = `Create collection "${query}"`;
                    createOption.classList.add("suggestion-item");
                    createOption.addEventListener("click", () => {
                        createAndAddCollection(query);
                        suggestionsBox.style.display = "none"; // Hide suggestions on creation
                    });
                    suggestionsBox.appendChild(createOption);
                }

                suggestionsBox.style.display = "block"; // Show suggestions
            })
            .catch((error) => {
                console.error("Error fetching collections:", error);
                suggestionsBox.innerHTML = `<div class="suggestion-item">Error fetching collections</div>`;
                suggestionsBox.style.display = "block";
            });
    }

    // Add collection to the deck
    function addCollectionToDeck(collectionId, collectionName) {
        console.log(`Adding collection "${collectionName}" (ID: ${collectionId}) to deck.`);
        fetch("/pages/manage_collection.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "add", deck_id: <?php echo $deck_id; ?>, collection_id: collectionId }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log(`Collection "${collectionName}" added successfully.`);
                    displayCollectionBubble(collectionId, collectionName);
                } else {
                    console.error("Error adding collection:", data.error);
                }
            })
            .catch((error) => console.error("Error adding collection:", error));
    }

    // Create a new collection and add to the deck
    function createAndAddCollection(collectionName) {
        console.log(`Creating and adding collection "${collectionName}".`);
        fetch("/pages/create_collection.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name: collectionName, deck_id: <?php echo $deck_id; ?> }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log(`Collection "${collectionName}" created successfully.`);
                    displayCollectionBubble(data.collection_id, collectionName);
                } else {
                    console.error("Error creating collection:", data.error);
                }
            })
            .catch((error) => console.error("Error creating collection:", error));
    }

    // Display collection bubble and add it to the top of the list
    function displayCollectionBubble(collectionId, collectionName) {
        if (addedCollections.has(collectionName)) return;

        const collectionItem = document.createElement("li");
        collectionItem.textContent = collectionName;
        collectionItem.classList.add("edit-deck-collection-item");
        collectionItem.dataset.collectionId = collectionId;

        const removeBtn = document.createElement("span");
        removeBtn.textContent = "x";
        removeBtn.classList.add("remove-btn");
        removeBtn.addEventListener("click", () => removeCollectionFromDeck(collectionId, collectionItem, collectionName));

        collectionItem.appendChild(removeBtn);

        // Add new collection at the top of the list
        collectionList.prepend(collectionItem);
        addedCollections.add(collectionName);
    }

    // Remove collection from the deck
    function removeCollectionFromDeck(collectionId, collectionItem, collectionName) {
        console.log(`Removing collection "${collectionName}" (ID: ${collectionId}) from deck.`);
        fetch("/pages/manage_collection.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "remove", deck_id: <?php echo $deck_id; ?>, collection_id: collectionId }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log(`Collection "${collectionName}" removed successfully.`);
                    collectionItem.remove();
                    addedCollections.delete(collectionName);
                } else {
                    console.error("Error removing collection:", data.error);
                }
            })
            .catch((error) => console.error("Error removing collection:", error));
    }
});
</script>
