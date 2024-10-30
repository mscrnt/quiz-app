<?php
# path: php/create_question.php

include 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$deck_id = $_POST['deck_id'] ?? $_GET['deck_id'] ?? null;
$question_id = $_POST['question_id'] ?? $_GET['question_id'] ?? null;

if (!$user_id || !$deck_id) {
    error_log("Error: User not logged in or deck ID missing.");
    exit("Error: User not logged in or deck ID missing.");
}

$question = null;
$answers = [];

// If editing an existing question, fetch its details and answers
if ($question_id) {
    $question_result = $conn->query("SELECT * FROM quiz_questions WHERE id = $question_id AND created_by = $user_id");
    $question = $question_result->fetch_assoc();

    // Fetch existing answers for multiple-choice questions
    if ($question && $question['question_type'] === 'multiple_choice') {
        $answers_result = $conn->query("SELECT * FROM quiz_answers WHERE question_id = $question_id");
        while ($answer = $answers_result->fetch_assoc()) {
            $answers[] = $answer;
        }
    }
}

// Process form submission for both adding and updating questions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = $_POST['question_text'];
    $question_type = $_POST['question_type'];

    if ($question_id) {
        // Update existing question
        $stmt = $conn->prepare("UPDATE quiz_questions SET question = ?, question_type = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ssii", $question_text, $question_type, $question_id, $user_id);
    } else {
        // Insert new question
        $stmt = $conn->prepare("INSERT INTO quiz_questions (question, question_type, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $question_text, $question_type, $user_id);
    }

    if ($stmt->execute()) {
        if (!$question_id) {
            $question_id = $stmt->insert_id;
            // Link the question to the specified deck if it's a new question
            $stmt_link = $conn->prepare("INSERT INTO deck_questions (deck_id, question_id) VALUES (?, ?)");
            $stmt_link->bind_param("ii", $deck_id, $question_id);
            $stmt_link->execute();
            $stmt_link->close();
        } else {
            // Delete existing answers for multiple-choice to update with new ones
            $conn->query("DELETE FROM quiz_answers WHERE question_id = $question_id");
        }
        $stmt->close();
    }

    // Insert answers if it's a multiple-choice question
    if ($question_type === 'multiple_choice' && isset($_POST['answers'])) {
        foreach ($_POST['answers'] as $index => $answer) {
            $answer_text = $answer['text'];
            $is_correct = isset($answer['is_correct']) ? 1 : 0;
            $answer_stmt = $conn->prepare("INSERT INTO quiz_answers (question_id, answer, is_correct) VALUES (?, ?, ?)");
            $answer_stmt->bind_param("isi", $question_id, $answer_text, $is_correct);
            $answer_stmt->execute();
            $answer_stmt->close();
        }
    }

    // JavaScript redirect to avoid full page load
    echo "<script>
            window.location.href = 'manage_decks.php?deck_id=$deck_id';
          </script>";
    exit;
}

// Form to be displayed when editing or creating a question
?>
<div class="card-content">
    <h2><?php echo $question_id ? "Edit Question" : "Add Question"; ?></h2>
    <form method="POST" action="create_question.php" id="question-form">
        <input type="hidden" name="deck_id" value="<?php echo $deck_id; ?>">
        <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">

        <label>Question Text:</label>
        <input type="text" name="question_text" value="<?php echo htmlspecialchars($question['question'] ?? ''); ?>" required>

        <label>Question Type:</label>
        <select name="question_type" onchange="toggleAnswerFields(this)">
            <option value="multiple_choice" <?php echo ($question['question_type'] ?? '') === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
            <option value="open_ended" <?php echo ($question['question_type'] ?? '') === 'open_ended' ? 'selected' : ''; ?>>Open Ended</option>
        </select>

        <div id="multiple-choice-answers" style="display: <?php echo ($question['question_type'] ?? '') === 'multiple_choice' ? 'block' : 'none'; ?>;">
            <h4>Answers (for multiple choice):</h4>
            <?php foreach ($answers as $index => $answer): ?>
                <div class="answer">
                    <input type="text" name="answers[<?php echo $index; ?>][text]" value="<?php echo htmlspecialchars($answer['answer']); ?>" required>
                    <label><input type="radio" name="correct_answer" value="<?php echo $index; ?>" <?php echo $answer['is_correct'] ? 'checked' : ''; ?>> Correct</label>
                </div>
            <?php endforeach; ?>
            <button type="button" onclick="addAnswer()">Add Answer</button>
        </div>

        <div class="button-container">
            <input type="submit" value="Save Question" class="save-button">
            <button type="button" class="cancel-button" onclick="document.querySelector('.card').classList.remove('flip');">Cancel</button>
        </div>
    </form>
</div>
<script>
    function toggleAnswerFields(select) {
        const container = document.getElementById('multiple-choice-answers');
        container.style.display = select.value === 'multiple_choice' ? 'block' : 'none';
    }

    function addAnswer() {
        const container = document.getElementById('multiple-choice-answers');
        const index = container.querySelectorAll('.answer').length;
        if (index < 6) { // Limit to 6 answers
            const newAnswer = document.createElement('div');
            newAnswer.className = 'answer';
            newAnswer.innerHTML = `
                <input type="text" name="answers[${index}][text]" placeholder="Answer ${index + 1}" required>
                <label><input type="radio" name="correct_answer" value="${index}"> Correct</label>
            `;
            container.appendChild(newAnswer);
        } else {
            alert("You can only add up to 6 answers.");
        }
    }
</script>
