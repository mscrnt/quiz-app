<?php
// path: php/question_form.php

include 'db.php';

$deck_id = $_GET['deck_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id || !$deck_id) {
    echo "<div><p>Deck not found or user not logged in.</p></div>";
    exit;
}

// Verify the deck association with the user
$deck_check = $conn->query("SELECT 1 FROM user_decks WHERE user_id = $user_id AND deck_id = $deck_id");

if ($deck_check->num_rows === 0) {
    echo "<div><p>Deck not found or you do not have access to it.</p></div>";
    exit;
}
?>

<div class="card-content">
    <h2>Add Question</h2>
    <form method="POST" action="create_question.php" id="question-form">
        <input type="hidden" name="deck_id" value="<?php echo $deck_id; ?>">

        <label>Question Text:</label>
        <input type="text" name="question_text" required>

        <label>Question Type:</label>
        <select name="question_type" onchange="toggleAnswerFields(this)">
            <option value="multiple_choice" selected>Multiple Choice</option>
            <option value="open_ended">Open Ended</option>
        </select>

        <div id="multiple-choice-answers">
            <h4>Answers (for multiple choice):</h4>
            <div class="answer">
                <input type="text" name="answers[0][text]" placeholder="Answer 1" required>
                <label><input type="radio" name="correct_answer" value="0"> Correct</label>
            </div>
            <div class="answer">
                <input type="text" name="answers[1][text]" placeholder="Answer 2" required>
                <label><input type="radio" name="correct_answer" value="1"> Correct</label>
            </div>
            <button type="button" onclick="addAnswer()">Add Answer</button>
        </div>

        <div class="button-container">
            <input type="submit" value="Save Question" class="save-button">
            <button type="button" class="cancel-button">Cancel</button>
        </div>
    </form>
</div>

<script>
    document.getElementById("question-form").addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent default form submission

        const formData = new FormData(this);
        fetch("create_question.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            document.querySelector(".card").classList.remove("flip"); // Show front of the card
            loadQuestions(<?php echo $deck_id; ?>); // Reload questions in the sidebar
        })
        .catch(error => console.error("Error submitting question:", error));
    });

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
