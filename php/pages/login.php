<?php
# path: php/pages/login.php

session_start();
include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/user_functions.php';  // Include user functions

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = $_POST['user_name'] ?? '';
    $password = $_POST['password'] ?? '';

    // Attempt to authenticate user
    $user = authenticateUser($user_name, $password);  // Updated function call

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        // Redirect to index.php after successful login
        header("Location: /index.php");
        exit;
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="/static/style.css"> <!-- Updated path -->
</head>
<body>
    <?php include __DIR__ . '/../templates/navbar.php'; ?>
    <div class="card-container">
        <div class="card">
            <div class="card-front">
                <h2>Login</h2>
                <?php if (!empty($error_message)): ?>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <form method="POST">
                    <label for="user_name">Username:</label>
                    <input type="text" id="user_name" name="user_name" required>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <input type="submit" value="Login">
                </form>
                <p>Don’t have an account? <a href="/pages/register.php">Register here</a>.</p> <!-- Updated path -->
            </div>
        </div>
    </div>
</body>
</html>
