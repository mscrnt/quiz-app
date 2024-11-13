<?php
# path: php/pages/setup.php

include_once __DIR__ . '/../includes/db_functions.php';
include __DIR__ . '/../templates/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? null;

    if ($mode === 'single_user' || $mode === 'multi_user') {
        // Update the configurations table with the selected mode
        $stmt = $conn->prepare("INSERT INTO configurations (id, mode) VALUES (1, ?) ON DUPLICATE KEY UPDATE mode = ?");
        $stmt->bind_param("ss", $mode, $mode);

        if ($stmt->execute()) {
            if ($mode === 'single_user') {
                header("Location: index.php");
            } else {
                header("Location: login.php");
            }
            exit;
        } else {
            echo "<p>Error updating site mode: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Invalid mode selected.</p>";
    }
}
?>
