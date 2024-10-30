<?php
# Path: php/upload_quiz.php

// Directory to save uploaded files
$uploadDir = '../uploads/';
$uploadedFile = $uploadDir . basename($_FILES['quiz_file']['name']);

// Ensure a file is uploaded
if (isset($_FILES['quiz_file']) && move_uploaded_file($_FILES['quiz_file']['tmp_name'], $uploadedFile)) {
    // Determine the file type based on extension
    $fileType = pathinfo($uploadedFile, PATHINFO_EXTENSION);

    // Connect to the database
    $conn = new mysqli(getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create a new quiz deck for the uploaded file
    $deck_name = pathinfo($uploadedFile, PATHINFO_FILENAME);
    $sql = "INSERT INTO quiz_decks (name) VALUES ('$deck_name')";
    $conn->query($sql);
    $deck_id = $conn->insert_id;

    // Parse the file based on its type (CSV or JSON)
    if ($fileType == 'csv') {
        $handle = fopen($uploadedFile, 'r');
        while (($data = fgetcsv($handle)) !== false) {
            $question = $data[0];
            $answer = $data[1];
            $sql = "INSERT INTO quiz_questions (question, answer, deck_id) VALUES ('$question', '$answer', $deck_id)";
            $conn->query($sql);
        }
        fclose($handle);
        echo "CSV file uploaded and processed!";
    } elseif ($fileType == 'json') {
        $json_data = file_get_contents($uploadedFile);
        $questions = json_decode($json_data, true);
        foreach ($questions as $question) {
            $sql = "INSERT INTO quiz_questions (question, answer, deck_id) VALUES ('" . $question['question'] . "', '" . $question['answer'] . "', $deck_id)";
            $conn->query($sql);
        }
        echo "JSON file uploaded and processed!";
    } else {
        echo "Unsupported file type. Please upload a CSV or JSON file.";
    }
} else {
    echo "Failed to upload file.";
}
?>
