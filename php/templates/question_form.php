<?php
// path: templates/question_form.php

include_once __DIR__ . '/../includes/db_functions.php';
include_once __DIR__ . '/../includes/deck_functions.php';

session_start();
$deck_id = $_GET['deck_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? null;

// Debugging logs for deck and user ID validation
error_log("Debug: User ID: $user_id, Deck ID (from GET): $deck_id");

if (!$user_id || !$deck_id) {
    error_log("Error: Deck not found or user not logged in.");
    echo json_encode(["success" => false, "message" => "Deck not found or user not logged in."]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = $_POST['question_text'] ?? '';
    $question_type = $_POST['question_type'] ?? 'multiple_choice';
    $answers = $_POST['answers'] ?? [];
    $correct_answer_index = $_POST['correct_answer'] ?? null;

    // Debugging log for submitted data
    error_log("Debug: Submitted Question - Text: $question_text, Type: $question_type, Deck ID: $deck_id");

    // Mark correct answers based on form selection
    foreach ($answers as $index => &$answer) {
        $answer['is_correct'] = ($index == $correct_answer_index);
        error_log("Debug: Answer $index - Text: {$answer['text']}, Is Correct: {$answer['is_correct']}");
    }

    // Save question and attach to deck
    $question_id = saveQuestion($deck_id, $question_text, $question_type, $answers);
    if ($question_id) {
        error_log("Debug: Question saved with ID: $question_id");
        echo json_encode(["success" => true, "message" => "Question saved successfully."]);
    } else {
        error_log("Error: Failed to save question.");
        echo json_encode(["success" => false, "message" => "Error saving question. Please try again."]);
    }
    exit;
}
?>

<div class="card-content">
    <h2><i class="fas fa-question-circle"></i> Add Question</h2>
    <form method="POST" action="/templates/question_form.php?deck_id=<?php echo $deck_id; ?>" id="question-form">
        <input type="hidden" name="deck_id" value="<?php echo $deck_id; ?>">

        <label><i class="fas fa-pencil-alt"></i> Question Text:</label>
        <input type="text" name="question_text" placeholder="Enter question text" required>

        <label><i class="fas fa-list"></i> Question Type:</label>
        <select name="question_type" id="question_type" onchange="toggleAnswerFields()">
            <option value="multiple_choice" selected>Multiple Choice</option>
            <option value="open_ended">Open Ended</option>
        </select>

        <div id="multiple-choice-answers">
            <h4><i class="fas fa-check-circle"></i> Answers (for multiple choice):</h4>
            <div class="answer">
                <input type="text" name="answers[0][text]" placeholder="Answer 1" required>
                <label><input type="radio" name="correct_answer" value="0"> Correct</label>
            </div>
            <div class="answer">
                <input type="text" name="answers[1][text]" placeholder="Answer 2" required>
                <label><input type="radio" name="correct_answer" value="1"> Correct</label>
            </div>
            <button type="button" id="addAnswerButton" class="btn-add-answer"><i class="fas fa-plus-circle"></i> Add Answer</button>
        </div>

        <div class="button-container">
            <input type="submit" value="Save Question" class="save-button" id="saveQuestionButton">
            <button type="button" class="cancel-button" id="cancelButton"><i class="fas fa-times-circle"></i> Cancel</button>
        </div>
    </form>
</div>

<script>
    // Define toggleAnswerFields function in the global scope
    function toggleAnswerFields() {
        const questionType = document.getElementById('question_type').value;
        const answerContainer = document.getElementById('multiple-choice-answers');
        answerContainer.style.display = questionType === 'multiple_choice' ? 'block' : 'none';
        console.log("Toggled answer fields. Question type:", questionType);
    }

    function addAnswer() {
        const container = document.getElementById("multiple-choice-answers");
        const index = container.querySelectorAll(".answer").length;

        if (index < 6) {
            const newAnswer = document.createElement("div");
            newAnswer.className = "answer";
            newAnswer.innerHTML = `
                <input type="text" name="answers[${index}][text]" placeholder="Answer ${index + 1}" required>
                <label><input type="radio" name="correct_answer" value="${index}"> Correct</label>
            `;
            container.appendChild(newAnswer);
            console.log(`Answer ${index + 1} added.`);
        } else {
            alert("You can only add up to 6 answers.");
        }
    }

    function initializeQuestionFormListeners() {
        const addAnswerButton = document.getElementById("addAnswerButton");
        const cancelButton = document.getElementById("cancelButton");

        if (addAnswerButton) {
            addAnswerButton.addEventListener("click", addAnswer);
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", () => {
                flipCardBack(); // Assuming this function is globally available for flipping back to the main view
            });
        }

        // Run toggleAnswerFields once to set initial display state
        toggleAnswerFields();
    }

    // Initialize listeners when the content is loaded
    document.addEventListener("DOMContentLoaded", initializeQuestionFormListeners);
</script>
