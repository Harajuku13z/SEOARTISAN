CREATE TABLE IF NOT EXISTS page_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    type ENUM('heading', 'text', 'image', 'gallery', 'list', 'button', 'faq', 'testimonials', 'form', 'map', 'cta', 'services', 'projects', 'service_area') NOT NULL,
    position INT NOT NULL DEFAULT 0,
    data JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_page_blocks_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE,
    INDEX idx_page_blocks_page_position (page_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
