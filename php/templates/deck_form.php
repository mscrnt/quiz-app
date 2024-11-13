<?php
// path: php/templates/deck_form.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit;
}

$multi_user_mode = getSiteMode() === 'multi_user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deck_name = $_POST['deck_name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    $minutes = is_numeric($_POST['time_limit_minutes']) ? (int)$_POST['time_limit_minutes'] : 0;
    $seconds = is_numeric($_POST['time_limit_seconds']) ? (int)$_POST['time_limit_seconds'] : 0;
    $time_limit = ($minutes * 60) + $seconds;

    $is_private = isset($_POST['is_private']) ? 1 : 0;
    $collection_ids = $_POST['collection_ids'] ?? [];
    $new_collection_name = $_POST['new_collection'] ?? '';

    if (!empty($new_collection_name)) {
        $new_collection_id = createCollection($new_collection_name, $user_id);
        if ($new_collection_id) {
            $collection_ids[] = $new_collection_id;
        }
    }

    $deck_id = createDeck($deck_name, $description, $time_limit, $user_id, $is_private, $collection_ids);

    if ($deck_id) {
        echo json_encode(['success' => true, 'deck_id' => $deck_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Deck creation failed.']);
    }
    exit;
}


$collections = getCollectionsByUser($user_id) ?? [];
?>

<h2 class="form-title"><i class="fas fa-plus-circle"></i> Create a New Deck</h2>

<form id="deckForm" class="styled-form">
    <div class="form-grid">
        <div class="form-group">
            <label for="deck_name"><i class="fas fa-book"></i> Deck Name:</label>
            <input type="text" id="deck_name" name="deck_name" required>
        </div>
        <div class="form-group">
            <label for="description"><i class="fas fa-align-left"></i> Description:</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <div class="form-group">
            <label for="time_limit"><i class="fas fa-clock"></i> Time Limit:</label>
            <div class="time-inputs">
                <input type="number" id="time_limit_minutes" name="time_limit_minutes" placeholder="Minutes" min="0">
                <span class="time-separator">:</span>
                <input type="number" id="time_limit_seconds" name="time_limit_seconds" placeholder="Seconds" min="0" max="59">
            </div>
        </div>
        <div class="form-group">
            <label for="collection_id"><i class="fas fa-folder-open"></i> Select Collections:</label>
            <select id="collection_id" name="collection_ids[]" multiple class="multi-select">
                <?php foreach ($collections as $collection): ?>
                    <option value="<?php echo $collection['id']; ?>"><?php echo htmlspecialchars($collection['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="new_collection"><i class="fas fa-plus"></i> Or Create New Collection:</label>
            <input type="text" id="new_collection" name="new_collection" placeholder="New Collection Name">
        </div>
        <?php if ($multi_user_mode): ?>
            <div class="form-group">
                <label for="is_private"><i class="fas fa-lock"></i> Make Deck Private:</label>
                <input type="checkbox" id="is_private" name="is_private" value="1" checked>
                <span class="tooltip" title="Once a deck is public, it cannot be made private again."><i class="fas fa-info-circle"></i></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="button-container">
        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Create Deck</button>
        <button type="button" class="btn btn-secondary" onclick="flipCardBack()"><i class="fas fa-times"></i> Cancel</button>
    </div>
</form>

<!-- Include select2 library for searchable dropdown -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script>
    // Initialize the searchable multi-select
    document.addEventListener("DOMContentLoaded", function() {
        $('#collection_id').select2({
            placeholder: "Select or search collections",
            width: '100%'
        });
    });
</script>
