document.addEventListener("DOMContentLoaded", () => {
    console.log("Document loaded. Initializing global event listeners in app.js.");

    const cardContainer = document.querySelector(".card");
    const frontContent = document.querySelector(".card-front");
    const backContent = document.querySelector(".card-back");

    initializeSidebarListeners();

    // --- Deck & Question Management Functions ---

    function showCreateDeckForm() {
        console.log("Attempting to load create deck form...");
        fetch("/templates/deck_form.php")
            .then(response => response.text())
            .then(html => {
                loadBackContent(html);
                console.log("Deck creation form loaded and card flipped.");
                document.getElementById("deckForm").addEventListener("submit", submitDeckForm);
            })
            .catch(error => console.error("Error loading create deck form:", error));
    }

    function submitDeckForm(event) {
        event.preventDefault();
        console.log("Submitting deck form...");
        const formData = new FormData(event.target);

        fetch("/templates/deck_form.php", {
            method: "POST",
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("Deck created successfully:", data.deck_id);
                showFlashMessage("Deck created successfully!");
                refreshSidebar();
                showEditDeckForm(data.deck_id);
            } else {
                console.error("Error creating deck:", data.error);
                showFlashMessage("Error creating deck: " + data.error);
            }
        })
        .catch(error => console.error("Error submitting deck form:", error));
    }

    function showEditDeckForm(deckId) {
        console.log(`Loading edit form for deck ID: ${deckId}`);
        
        // Clear previous content from the back side of the card
        backContent.innerHTML = '';
        
        fetch(`/templates/edit_deck.php?deck_id=${deckId}`)
            .then(response => response.text())
            .then(html => {
                loadBackContent(html);
                console.log("Edit deck form content loaded.");
        
                // Ensure listeners are properly attached without duplication
                attachDeckEventListeners(deckId);
                
                // Highlight the selected deck link
                highlightActiveDeck(deckId);
            })
            .catch(error => console.error("Error loading edit deck form:", error));
    }
    
    // Function to highlight the selected deck link
    function highlightActiveDeck(deckId) {
        // Remove the active class from any previously highlighted deck link
        document.querySelectorAll('.deck-link').forEach(link => {
            link.classList.remove('active-deck');
        });
        
        // Add the active class to the selected deck link
        const selectedLink = document.querySelector(`.deck-link[data-deck-id="${deckId}"]`);
        if (selectedLink) {
            selectedLink.classList.add('active-deck');
        }
    }
    
    

    function attachDeckEventListeners(deckId) {
        // Use unique IDs for each button
        const saveButton = document.getElementById(`saveChangesButton_${deckId}`);
        const addQuestionButton = document.getElementById(`addQuestionButton_${deckId}`);
        const deleteButton = document.getElementById(`deleteDeckButton_${deckId}`);
    
        if (saveButton && addQuestionButton && deleteButton) {
            saveButton.replaceWith(saveButton.cloneNode(true));
            addQuestionButton.replaceWith(addQuestionButton.cloneNode(true));
            deleteButton.replaceWith(deleteButton.cloneNode(true));
    
            saveButton.addEventListener("click", () => saveChanges(deckId));
            addQuestionButton.addEventListener("click", () => loadQuestionForm(deckId));
            deleteButton.addEventListener("click", () => deleteDeck(deckId));
        } else {
            console.error(`Failed to attach listeners: elements not found for deck ID ${deckId}`);
        }
    }

    function loadQuestionForm(deckId) {
        console.log(`Attempting to load question form for deck ID: ${deckId}`);
        fetch(`/templates/question_form.php?deck_id=${deckId}`)
            .then(response => response.text())
            .then(html => {
                loadBackContent(html);
                console.log("Question form loaded and card flipped.");
            })
            .catch(error => console.error("Error loading question form:", error));
    }

    function deleteDeck(deckId) {
        console.log("Delete Deck button clicked. Loading confirmation dialog.");
        
        fetch(`/templates/confirm_delete.php`)
            .then(response => response.text())
            .then(html => {
                loadBackContent(html); // Load confirmation prompt into the back of the card
                console.log("Delete confirmation loaded.");
    
                // Flip the card to display the confirmation dialog
                if (!cardContainer.classList.contains("flip")) {
                    cardContainer.classList.add("flip");
                }
    
                // Attach event listeners for confirm and cancel buttons
                document.getElementById("confirmDeleteButton").addEventListener("click", () => confirmDelete(deckId));
                document.getElementById("cancelDeleteButton").addEventListener("click", flipCardBack);
            })
            .catch(error => console.error("Error loading delete confirmation:", error));
    }
    
    
    
    function confirmDelete(deckId) {
        console.log("Confirm delete clicked. Proceeding with deletion.");
        
        fetch(`/templates/edit_deck.php?deck_id=${deckId}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showFlashMessage("Deck deleted successfully!", deckId);
                refreshSidebar();
                
                // Check if there's a next deck to load
                if (result.next_deck_id) {
                    showEditDeckForm(result.next_deck_id);
                } else {
                    // If no more decks, show a message and flip card back
                    loadBackContent("<p>No more decks available.</p>");
                }
    
                // Flip the card back to the front view
                flipCardBack();
            } else {
                console.error("Error deleting deck:", result.error);
                showFlashMessage("Error deleting deck: " + result.error, deckId);
            }
        })
        .catch(error => console.error("Request error during deck deletion:", error));
    }
    
    
    
    

    function saveChanges(deckId) {
        console.log("Save Changes button clicked for deck ID:", deckId);
    
        const deckName = document.getElementById(`deck_name_${deckId}`).value.trim();
        const deckDescription = document.getElementById(`deck_description_${deckId}`).value.trim();
        const timeLimitMinutes = document.getElementById(`time_limit_minutes_${deckId}`).value.trim();
        const timeLimitSeconds = document.getElementById(`time_limit_seconds_${deckId}`).value.trim();
        const isPublic = document.getElementById(`is_public_${deckId}`).checked ? '1' : '0';
    
        const formData = new FormData();
        formData.append("deck_name", deckName);
        formData.append("deck_description", deckDescription);
        formData.append("time_limit_minutes", timeLimitMinutes);
        formData.append("time_limit_seconds", timeLimitSeconds);
        formData.append("is_public", isPublic);
    
        fetch(`/templates/edit_deck.php?deck_id=${deckId}`, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showFlashMessage("SAVED!", deckId);
                refreshSidebar();
            } else {
                showFlashMessage("Error updating deck: " + result.error, deckId);
            }
        })
        .catch(error => console.error("Request error:", error));
    }
    
    function deleteQuestion(deckId, questionId) {
        console.log(`Attempting to delete question ID: ${questionId} from deck ID: ${deckId}`);
        if (!confirm("Are you sure you want to delete this question?")) return;

        fetch(`/delete_question.php`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ question_id: questionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("Question deleted successfully.");
                loadDeckDetails(deckId);
            } else {
                console.error("Error deleting question:", data.error);
            }
        })
        .catch(error => console.error("Request error:", error));
    }

    // --- General UI Functions ---

    function flipCardBack() {
        if (cardContainer && cardContainer.classList.contains("flip")) {
            cardContainer.classList.remove("flip");
            console.log("Card flipped back.");
        }
    }

    function loadBackContent(html) {
        console.log("Loading content into back side of the card.");
        if (backContent) {
            // Clear old content before loading new content
            backContent.innerHTML = '';
            backContent.innerHTML = html;
            if (!cardContainer.classList.contains("flip")) {
                cardContainer.classList.add("flip");
            }
        }
    }
    

    // --- Sidebar Listeners and Refresh ---

    function initializeSidebarListeners() {
        console.log("Initializing sidebar listeners.");
        document.querySelectorAll(".deck-link").forEach(deckLink => {
            deckLink.addEventListener("click", (e) => {
                e.preventDefault();
                const deckId = e.target.dataset.deckId;
                console.log(`Deck link clicked. Loading deck ID: ${deckId}`);
                showEditDeckForm(deckId);
            });
        });
        initializeCreateDeckButton();

        document.querySelectorAll(".sidebar-section h3").forEach(header => {
            header.addEventListener("click", () => {
                const section = header.nextElementSibling;
                const icon = header.querySelector('.toggle-icon');
                section.style.display = (section.style.display === 'none') ? 'block' : 'none';
                icon.classList.toggle('fa-caret-down');
                icon.classList.toggle('fa-caret-right');
                console.log(`Toggled section: ${section.id}`);
            });
        });
    }

    function refreshSidebar() {
        console.log("Refreshing sidebar.");
        fetch('/templates/sidebar.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('sidebar').innerHTML = html;
                initializeSidebarListeners();
            })
            .catch(error => console.error("Error refreshing sidebar:", error));
    }

    function initializeCreateDeckButton() {
        const createDeckButton = document.getElementById("create-deck-button");
        if (createDeckButton) {
            createDeckButton.addEventListener("click", (e) => {
                e.preventDefault();
                console.log("Create Deck button clicked.");
                showCreateDeckForm();
            });
        } else {
            console.error("Create Deck button not found.");
        }
    }

    // --- Flash Message Function ---

    function showFlashMessage(message, deckId) {
        const flashMessage = document.getElementById(`flashMessage_${deckId}`);
        if (flashMessage) {
            flashMessage.textContent = message;
            flashMessage.style.display = "block";
            setTimeout(() => {
                flashMessage.style.display = "none";
            }, 2000);
        } else {
            console.error("Flash message element not found for deck ID:", deckId);
        }
    }

    // Expose functions globally
    window.showCreateDeckForm = showCreateDeckForm;
    window.loadQuestionForm = loadQuestionForm;
    window.showEditDeckForm = showEditDeckForm;
    window.deleteQuestion = deleteQuestion;
    window.flipCardBack = flipCardBack;
    window.saveChanges = saveChanges;
    window.deleteDeck = deleteDeck;
});
