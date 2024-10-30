// path: static/sidebar.js

document.addEventListener("DOMContentLoaded", function() {
    const cardContainer = document.querySelector(".card");
    const frontContent = document.querySelector(".card-front");

    function loadDeckDetails(deckId) {
        fetch(`deck_details.php?deck_id=${deckId}`)
            .then(response => response.text())
            .then(html => {
                frontContent.innerHTML = html;
                cardContainer.classList.remove("flip"); // Reset to front side
                loadQuestions(deckId);
            })
            .catch(error => console.error("Error fetching deck details:", error));
    }

    function loadQuestionForm(deckId) {
        fetch(`question_form.php?deck_id=${deckId}`)
            .then(response => response.text())
            .then(html => {
                const backContent = document.querySelector(".card-back");
                backContent.innerHTML = html;
                const card = document.querySelector(".card");
                if (!card.classList.contains("flip")) {
                    card.classList.add("flip");
                }
            })
            .catch(error => console.error("Error loading question form:", error));
    }

    function loadQuestions(deckId) {
        fetch(`get_questions.php?deck_id=${deckId}`)
            .then(response => response.text())
            .then(html => {
                const questionList = document.getElementById(`questions-${deckId}`);
                questionList.innerHTML = html;
                questionList.style.display = "block";
                attachQuestionListeners(deckId);
            })
            .catch(error => console.error("Error fetching questions:", error));
    }

    function attachQuestionListeners(deckId) {
        document.querySelectorAll(`#questions-${deckId} .question-link`).forEach(question => {
            question.addEventListener("click", function(event) {
                event.preventDefault();
                const questionId = question.getAttribute("data-question-id");
                loadQuestionDetails(deckId, questionId);
            });
        });

        const addQuestionLink = document.querySelector(`#questions-${deckId} .add-question-link`);
        if (addQuestionLink) {
            addQuestionLink.addEventListener("click", function(event) {
                event.preventDefault();
                loadQuestionForm(deckId);
            });
        }
    }

    function loadQuestionDetails(deckId, questionId) {
        fetch(`question_details.php?deck_id=${deckId}&question_id=${questionId}`)
            .then(response => response.text())
            .then(html => {
                frontContent.innerHTML = html;
                cardContainer.classList.remove("flip"); 
            })
            .catch(error => console.error("Error fetching question details:", error));
    }

    document.querySelectorAll(".deck-link").forEach(link => {
        link.addEventListener("click", function(event) {
            event.preventDefault();
            const deckId = link.getAttribute("data-deck-id");
            loadDeckDetails(deckId);
        });
    });

    document.addEventListener("click", function(event) {
        if (event.target && event.target.classList.contains("cancel-button")) {
            event.preventDefault();
            cardContainer.classList.remove("flip");
        }
    });
});
