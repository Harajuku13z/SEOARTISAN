CREATE TABLE IF NOT EXISTS ai_generations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(30) NOT NULL,
    model VARCHAR(120) NULL,
    prompt_type VARCHAR(60) NOT NULL,
    prompt LONGTEXT NULL,
    response LONGTEXT NULL,
    tokens_used INT UNSIGNED NULL,
    estimated_cost DECIMAL(8,4) NULL,
    status ENUM('success', 'failed', 'retried') NOT NULL,
    error_message TEXT NULL,
    page_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_generations_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE SET NULL,
    CONSTRAINT fk_ai_generations_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_ai_generations_status (status),
    INDEX idx_ai_generations_prompt_type (prompt_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
