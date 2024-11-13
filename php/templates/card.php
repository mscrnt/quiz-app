<?php
// path: templates/card.php

// Default contents for front and back, in case they are not set
if (!isset($front_content)) {
    $front_content = '<h2>Welcome</h2><p>Select a deck or add a question to start.</p>';
}
if (!isset($back_content)) {
    $back_content = ''; // Initialize back content as empty
}
?>

<div class="card-container">
    <div class="card">
        <div class="card-front">
            <div id="cardFrontContent">
                <?php echo $front_content; ?>
            </div>
        </div>
        <div class="card-back">
            <div id="cardBackContent">
                <?php echo $back_content; ?>
            </div>
        </div>
    </div>
</div>
