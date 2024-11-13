<?php
# path: php/pages/setup_single_user.php

include_once __DIR__ . '/../includes/db_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = $_POST['user_name'] ?? '';

    if (!empty($user_name)) {
        // Insert user into users table if not exists
        $stmt = $conn->prepare("INSERT IGNORE INTO users (name) VALUES (?)");
        $stmt->bind_param("s", $user_name);

        if ($stmt->execute()) {
            $_SESSION['user_name'] = $user_name;
            header("Location: index.php");
            exit;
        } else {
            echo "<p>Error setting up single user: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>User name is required.</p>";
    }
}
?>
