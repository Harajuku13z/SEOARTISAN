-- Raw ingestion log for every form POST (quote requests + contact form).
-- Source of truth for spam heuristics; qualifying rows create a `leads` row.
CREATE TABLE IF NOT EXISTS form_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_type ENUM('quote', 'contact') NOT NULL,
    payload JSON NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    is_spam TINYINT(1) NOT NULL DEFAULT 0,
    spam_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_form_submissions_type (form_type),
    INDEX idx_form_submissions_is_spam (is_spam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
