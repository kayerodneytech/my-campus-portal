CREATE TABLE src_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL UNIQUE
);


CREATE TABLE src_candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    manifesto TEXT,
    image_path VARCHAR(255),
    position_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES src_positions(id) ON DELETE CASCADE
);

CREATE TABLE src_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    position_id INT,
    candidate_id INT,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, position_id), -- only one vote per position
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES src_candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES src_positions(id) ON DELETE CASCADE
);


CREATE TABLE src_settings (
    id INT PRIMARY KEY,
    is_voting_open BOOLEAN DEFAULT TRUE,
    voting_deadline DATETIME
);


INSERT INTO src_settings (id, is_voting_open, voting_deadline)
VALUES (1, TRUE, '2025-08-10 23:59:59');