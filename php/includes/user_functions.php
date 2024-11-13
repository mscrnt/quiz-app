<?php
# path: includes/user_functions.php

include_once 'db.php';

/**
 * Registers a new user.
 * 
 * @param string $user_name The username of the new user.
 * @param string $password The password for the new user.
 * @return array|bool Returns user data on success or an error message array on failure.
 */
function registerUser($user_name, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return ["error" => "Database error."];
    }

    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ["error" => "Username already taken. Please choose another."];
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (name, password) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return ["error" => "Database error."];
    }

    $stmt->bind_param("ss", $user_name, $hashed_password);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        return ["user_id" => $user_id, "user_name" => $user_name];
    } else {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return ["error" => "Error during registration: " . $conn->error];
    }
}

/**
 * Logs in a user.
 * 
 * @param string $user_name The username.
 * @param string $password The password.
 * @return array|bool Returns user data on success or an error message on failure.
 */
function loginUser($user_name, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE name = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return ["error" => "Database error."];
    }

    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $stmt->bind_result($user_id, $hashed_password);
    $stmt->fetch();

    if ($user_id && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $stmt->close();
        return ["user_id" => $user_id, "user_name" => $user_name];
    } else {
        $stmt->close();
        return ["error" => "Invalid username or password."];
    }
}

/**
 * Gets the current user based on the session.
 * 
 * @return array|bool Returns user data if logged in, or false if not logged in.
 */
function getCurrentUser() {
    if (isset($_SESSION['user_id'], $_SESSION['user_name'])) {
        return [
            "user_id" => $_SESSION['user_id'],
            "user_name" => $_SESSION['user_name']
        ];
    }
    return false;
}

/**
 * Sets up a single-user mode with the given username.
 * 
 * @param string $user_name The username for single-user mode.
 * @return bool Success or failure of user creation.
 */
function setupSingleUser($user_name) {
    global $conn;

    $stmt = $conn->prepare("INSERT IGNORE INTO users (name) VALUES (?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $user_name);
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_id'] = $conn->insert_id;
        $stmt->close();
        return true;
    } else {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Updates the site configuration mode to single-user or multi-user.
 * 
 * @param string $mode The mode ('single_user' or 'multi_user').
 * @return bool True on success, false on failure.
 */
function updateSiteMode($mode) {
    global $conn;

    if (!in_array($mode, ['single_user', 'multi_user'])) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO configurations (id, mode) VALUES (1, ?) ON DUPLICATE KEY UPDATE mode = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ss", $mode, $mode);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Logs out the current user by destroying the session.
 */
function logoutUser() {
    session_unset();
    session_destroy();
}
?>
