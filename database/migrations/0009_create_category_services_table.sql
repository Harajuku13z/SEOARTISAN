CREATE TABLE IF NOT EXISTS category_services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_category_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_category_services_category FOREIGN KEY (business_category_id) REFERENCES business_categories (id) ON DELETE CASCADE,
    CONSTRAINT fk_category_services_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    UNIQUE KEY uniq_category_service (business_category_id, service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
