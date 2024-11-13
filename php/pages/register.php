<?php
# path: php/pages/register.php

include_once __DIR__ . '/../includes/db_functions.php';
include __DIR__ . '/../templates/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = $_POST['user_name'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($user_name) && !empty($password)) {
        // Check if the username is already taken
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "<p>Username already taken. Please choose another.</p>";
        } else {
            // Insert new user into users table
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $user_name, $hashed_password);

            if ($stmt->execute()) {
                $success_message = "<p>Registration successful. You can now <a href='login.php'>login</a>.</p>";
            } else {
                $error_message = "<p>Error during registration: " . $conn->error . "</p>";
            }
        }
        $stmt->close();
    } else {
        $error_message = "<p>All fields are required.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="static/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="card-container">
        <div class="card">
            <div class="card-front">
                <h2>Register</h2>
                <?php if (isset($error_message)) echo $error_message; ?>
                <?php if (isset($success_message)) echo $success_message; ?>
                <form method="POST">
                    <label for="user_name">Username:</label>
                    <input type="text" id="user_name" name="user_name" required>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <input type="submit" value="Register">
                </form>
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
            </div>
            <div class="card-back">
                <!-- Additional content could go here if making it flippable -->
            </div>
        </div>
    </div>
</body>
</html>
