<?php
// path: php/card.php

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
            <?php echo $front_content; ?>
        </div>
        <div class="card-back">
            <?php echo $back_content; ?>
        </div>
    </div>
</div>
