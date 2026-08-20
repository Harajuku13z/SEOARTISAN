-- Scaffolding for the phase-2 mass local-page generator (service x ville).
-- Created now, per the spec, even though no generator writes to it yet.
CREATE TABLE IF NOT EXISTS local_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_service_id INT UNSIGNED NOT NULL,
    city_id INT UNSIGNED NOT NULL,
    page_id INT UNSIGNED NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    generation_status ENUM('pending', 'generating', 'generated', 'failed') NOT NULL DEFAULT 'pending',
    uniqueness_score TINYINT UNSIGNED NULL,
    quality_score TINYINT UNSIGNED NULL,
    published_at DATETIME NULL,
    last_generated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_local_pages_company_service FOREIGN KEY (company_service_id) REFERENCES company_services (id) ON DELETE CASCADE,
    CONSTRAINT fk_local_pages_city FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE CASCADE,
    CONSTRAINT fk_local_pages_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE SET NULL,
    UNIQUE KEY uniq_local_page (company_service_id, city_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
