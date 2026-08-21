CREATE TABLE IF NOT EXISTS conversion_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    element_text VARCHAR(255) NULL,
    target_url VARCHAR(500) NULL,
    page_path VARCHAR(500) NOT NULL,
    session_id VARCHAR(64) NULL,
    ip_hash CHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    is_human TINYINT(1) NOT NULL DEFAULT 0,
    bot_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    rejection_reason VARCHAR(500) NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversion_events_type (event_type),
    INDEX idx_conversion_events_human (is_human),
    INDEX idx_conversion_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
