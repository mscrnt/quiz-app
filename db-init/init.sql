-- db-init/init.sql

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Collections Table
CREATE TABLE IF NOT EXISTS collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_id INT NOT NULL,
    is_private BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Quiz Decks Table (depends on users)
CREATE TABLE IF NOT EXISTS quiz_decks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    time_limit INT DEFAULT NULL,
    created_by INT,  -- Allow NULL for ON DELETE SET NULL
    is_private BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- User Collections Table (depends on users and collections)
CREATE TABLE IF NOT EXISTS user_collections (
    user_id INT NOT NULL,
    collection_id INT NOT NULL,
    PRIMARY KEY (user_id, collection_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
);

-- User Decks Table (depends on users and quiz_decks)
CREATE TABLE IF NOT EXISTS user_decks (
    user_id INT NOT NULL,
    deck_id INT NOT NULL,
    PRIMARY KEY (user_id, deck_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (deck_id) REFERENCES quiz_decks(id) ON DELETE CASCADE
);

-- Collection-Deck Association Table (depends on collections and quiz_decks)
CREATE TABLE IF NOT EXISTS collection_decks (
    collection_id INT NOT NULL,
    deck_id INT NOT NULL,
    PRIMARY KEY (collection_id, deck_id),
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    FOREIGN KEY (deck_id) REFERENCES quiz_decks(id) ON DELETE CASCADE
);

-- Quiz Questions Table (depends on users)
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'open_ended') NOT NULL,
    possible_answers JSON DEFAULT NULL,
    correct_answer VARCHAR(255) DEFAULT NULL,
    created_by INT,  -- Allow NULL for ON DELETE SET NULL
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Deck-Question Association Table (depends on quiz_decks and quiz_questions)
CREATE TABLE IF NOT EXISTS deck_questions (
    deck_id INT NOT NULL,
    question_id INT NOT NULL,
    PRIMARY KEY (deck_id, question_id),
    FOREIGN KEY (deck_id) REFERENCES quiz_decks(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Quiz Answers Table (depends on quiz_questions)
CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Quiz Attempts Table (depends on users and quiz_decks)
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    deck_id INT NOT NULL,
    score INT DEFAULT 0,
    correct_questions JSON DEFAULT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (deck_id) REFERENCES quiz_decks(id) ON DELETE CASCADE
);