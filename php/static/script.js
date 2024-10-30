// path: static/script.js

function showCreateDeckForm() {
    const form = document.getElementById('create-deck-form');
    form.classList.toggle('hidden');
}

function addQuestion() {
    const questionsDiv = document.getElementById('questions');
    const questionCount = questionsDiv.getElementsByClassName('question').length + 1;

    const newQuestionDiv = document.createElement('div');
    newQuestionDiv.className = 'question';
    newQuestionDiv.innerHTML = `
        <label for="question${questionCount}">Question ${questionCount}:</label>
        <input type="text" id="question${questionCount}" name="questions[]" required>
        <label for="answer${questionCount}">Answer:</label>
        <input type="text" id="answer${questionCount}" name="answers[]" required>
    `;

    questionsDiv.appendChild(newQuestionDiv);
}

function resetCardFlip() {
    const card = document.querySelector('.card-container .card');
    if (card && card.classList.contains("flip")) {
        card.classList.remove("flip");
    }
}

// Function to load the question form into the back of the card for editing
function loadEditQuestionForm(deckId, questionId) {
    fetch(`create_question.php?deck_id=${deckId}&question_id=${questionId}`)
        .then(response => response.text())
        .then(html => {
            const backContent = document.querySelector(".card-back");
            backContent.innerHTML = html;  // Load form into the back of the card
            const card = document.querySelector(".card");
            if (!card.classList.contains("flip")) {
                card.classList.add("flip");  // Flip card to show form if not already flipped
            }
        })
        .catch(error => console.error("Error loading edit question form:", error));
}

// Quiz form submission handling
document.getElementById('quiz-form').addEventListener('submit', function (event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const request = new XMLHttpRequest();
    request.open('POST', 'submit_answer.php', true);

    request.onload = function () {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            const flashcardBack = document.getElementById('flashcard-back');
            flashcardBack.innerHTML = response.correct ? '<h2 class="correct">Correct!</h2>' : '<h2 class="incorrect">Incorrect. Correct answer: ' + response.correct_answer + '</h2>';
            flashcardBack.classList.add('flip');
        }
    };

    request.send(formData); 
});
