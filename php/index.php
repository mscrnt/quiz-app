<?php
// path: ./index.php

ob_start();
session_start();

include __DIR__ . '/includes/db_functions.php';

$site_mode = getSiteMode();

if ($site_mode === null) {
    $front_content = '
        <h2>Welcome to the Quiz App</h2>
        <p>Please choose the setup mode for your site:</p>
        <form method="POST" action="pages/setup.php">
            <label>
                <input type="radio" name="mode" value="single_user" required> Single User
            </label>
            <label>
                <input type="radio" name="mode" value="multi_user"> Multi User
            </label>
            <br>
            <input type="submit" value="Start Setup">
        </form>';
} else {
    if ($site_mode === 'multi_user' && !isset($_SESSION['user_id'])) {
        header("Location: pages/login.php");
        exit;
    }

    $front_content = '<h2>Welcome to the Quiz App</h2>';
    
    if ($site_mode === 'single_user') {
        $existing_user = getOrCreateSingleUser();
        if ($existing_user && !isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['user_name'] = $existing_user['name'];
        }

        if (!isset($_SESSION['user_name'])) {
            $front_content .= '
                <form method="POST" action="pages/setup_single_user.php">
                    <label for="user_name">Enter your name:</label>
                    <input type="text" id="user_name" name="user_name" required>
                    <input type="submit" value="Submit">
                </form>';
        } else {
            $front_content .= '<h3>Hello, ' . htmlspecialchars($_SESSION['user_name']) . '!</h3>';
        }
    } else {
        $front_content .= '<h3>Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!</h3>';
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App</title>
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <div class="container">
        <div class="card-container">
            <div class="card">
                <div class="card-front">
                    <?= $front_content; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
