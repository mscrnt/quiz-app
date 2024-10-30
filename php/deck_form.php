<?php
# path: php/deck_form.php

include 'db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    error_log("Error: User not logged in.");
    echo "Error: User not logged in.";
    exit;
}

$collections = $conn->query("SELECT * FROM collections WHERE owner_id = $user_id ORDER BY created_at DESC");

if (!$collections) {
    error_log("Error fetching collections: " . $conn->error); // Log the specific error
    echo "<p>Error loading collections. Please try again later.</p>";
    exit;
}

?>

<div class="card-container">
    <div class="card">
        <h2>Create a New Deck</h2>
        <form method="POST" action="create_deck.php">
            <label for="deck_name">Deck Name:</label>
            <input type="text" id="deck_name" name="deck_name" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description"></textarea>

            <label for="time_limit">Time Limit:</label>
            <input type="number" id="time_limit_minutes" name="time_limit_minutes" placeholder="Minutes" min="0">
            <input type="number" id="time_limit_seconds" name="time_limit_seconds" placeholder="Seconds" min="0" max="59">

            <label for="collection_id">Select Collections:</label>
            <select id="collection_id" name="collection_ids[]" multiple>
                <?php while ($collection = $collections->fetch_assoc()) : ?>
                    <option value="<?php echo $collection['id']; ?>"><?php echo htmlspecialchars($collection['name']); ?></option>
                <?php endwhile; ?>
            </select>

            <label for="new_collection">Or Create New Collection:</label>
            <input type="text" id="new_collection" name="new_collection" placeholder="New Collection Name">

            <button type="submit">Create Deck</button> <!-- Add the missing submit button -->
        </form>
    </div>
</div>