<?php
# path: php/includes/db_functions.php

include_once __DIR__ . '/db.php';  // Ensure this initializes $conn as a mysqli object

/**
 * Get the site mode configuration (e.g., single_user, multi_user)
 *
 * @return string|null Site mode or null if not set
 */
function getSiteMode() {
    global $conn;

    $result = $conn->query("SELECT mode FROM configurations WHERE id = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['mode'] ?? null;
    }
    return null;
}

/**
 * Update site mode configuration
 *
 * @param string $mode Site mode (e.g., single_user, multi_user)
 * @return bool True if successful, False otherwise
 */
function setSiteMode($mode) {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO configurations (id, mode) VALUES (1, ?) ON DUPLICATE KEY UPDATE mode = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ss", $mode, $mode);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Retrieve the current user by session ID
 *
 * @param int $user_id User ID from the session
 * @return array|null User data array or null if not found
 */
function getUserById($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $user;
}

/**
 * Retrieve or create a single-user profile
 *
 * @return array|null Existing or newly created user data
 */
function getOrCreateSingleUser() {
    global $conn;

    $result = $conn->query("SELECT id, name FROM users LIMIT 1");
    if ($result && $user = $result->fetch_assoc()) {
        return $user;
    }

    $stmt = $conn->prepare("INSERT INTO users (name) VALUES ('Default User')");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    $stmt->execute();
    $user = getUserById($stmt->insert_id);
    $stmt->close();

    return $user;
}

/**
 * Authenticate user by name and password.
 *
 * @param string $user_name User's name
 * @param string $password User's password
 * @return array|null User data if authenticated, null otherwise
 */
function authenticateUser($user_name, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE name = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $stmt->bind_result($user_id, $hashed_password);
    $stmt->fetch();
    $stmt->close();

    if ($user_id && password_verify($password, $hashed_password)) {
        return ['id' => $user_id, 'name' => $user_name];
    }
    return null;
}

/**
 * Executes a prepared query (INSERT, UPDATE, DELETE) with bound parameters.
 *
 * @param string $query SQL query with placeholders
 * @param string $types A string that contains one character per parameter type (e.g., 'i' for integers)
 * @param array $params Array of parameters to bind to the query
 * @return mysqli_stmt|bool The executed statement on success or false on failure
 */
function executePreparedQuery($query, $types, $params) {
    global $conn;

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    return $stmt;
}

/**
 * Executes a SELECT prepared query with bound parameters and returns the result set.
 *
 * @param string $query SQL query with placeholders
 * @param string $types A string that contains one character per parameter type (e.g., 'i' for integers)
 * @param array $params Array of parameters to bind to the query
 * @return mysqli_result|bool The result set or false on failure
 */
function fetchPreparedQuery($query, $types, $params) {
    global $conn;

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

?>
